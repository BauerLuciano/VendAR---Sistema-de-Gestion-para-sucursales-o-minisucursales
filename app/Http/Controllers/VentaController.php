<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\TurnoCaja;
use App\Models\Consumidor;
use App\Models\CuentaCorriente;
use App\Models\MovimientoCuentaCorriente;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $sucursalId = auth()->user()->branch_id ?? 1;
        
        $search = $request->input('search');
        $estado = $request->input('estado', 'all');
        $fecha_desde = $request->input('fecha_desde');
        $fecha_hasta = $request->input('fecha_hasta');

        $ventas = Venta::with(['consumidor', 'turno.cajero', 'turno.caja', 'detalles.producto'])
            ->whereHas('turno.caja', function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            })
            ->when($search, function ($q, $search) {
                $q->where('id', 'LIKE', "%{$search}%");
            })
            ->when($estado !== 'all', function ($q) use ($estado) {
                $q->where('estado', $estado);
            })
            ->when($fecha_desde, function ($q, $fecha_desde) {
                $q->whereDate('created_at', '>=', $fecha_desde);
            })
            ->when($fecha_hasta, function ($q, $fecha_hasta) {
                $q->whereDate('created_at', '<=', $fecha_hasta);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Ventas/Index', [
            'ventas' => $ventas,
            'filtros' => $request->only(['search', 'estado', 'fecha_desde', 'fecha_hasta'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'turno_caja_id' => 'required|exists:turno_cajas,id',
            'consumidor_id' => 'nullable|exists:consumidores,id', 
            'items'         => 'required|array|min:1',
            'total'         => 'required|numeric|min:0',
            'metodo_pago'   => 'required|string',
        ]);

        if ($request->metodo_pago === 'Cuenta Corriente' && !$request->consumidor_id) {
            return redirect()->back()->withErrors(['error' => 'Debe seleccionar un cliente para fiar.']);
        }

        try {
            DB::beginTransaction();

            $turno = TurnoCaja::with('caja')->findOrFail($request->turno_caja_id);
            $sucursalId = $turno->caja->sucursal_id;

            foreach ($request->items as $item) {
                $stockActual = DB::table('producto_sucursal')
                    ->where('producto_id', $item['id'])
                    ->where('sucursal_id', $sucursalId)
                    ->lockForUpdate() 
                    ->first();

                if (!$stockActual || $stockActual->cantidad_fisica < $item['cantidad']) {
                    $nombre = $item['nombre'] ?? "Producto ID: {$item['id']}";
                    $disp = $stockActual ? $stockActual->cantidad_fisica : 0;
                    throw new \Exception("Stock insuficiente para: {$nombre}. Disponible: {$disp}");
                }
            }

            $venta = Venta::create([
                'turno_caja_id' => $request->turno_caja_id,
                'consumidor_id' => $request->consumidor_id,
                'metodo_pago'   => $request->metodo_pago,
                'total'         => $request->total,
                'estado'        => 'Completada',
            ]);

            if ($request->metodo_pago === 'Cuenta Corriente') {
                $cuenta = CuentaCorriente::firstOrCreate(
                    ['consumidor_id' => $request->consumidor_id],
                    ['saldo_deudor' => 0]
                );
                
                $cuenta->increment('saldo_deudor', $request->total);
                
                MovimientoCuentaCorriente::create([
                    'cuenta_corriente_id' => $cuenta->id,
                    'venta_id'            => $venta->id,
                    'monto'               => $request->total,
                    'tipo'                => 'cargo',
                    'descripcion'         => 'Compra en POS',
                ]);
            } 
            else {
                $metodoPagoCaja = strtoupper(str_replace(' ', '_', $request->metodo_pago));

                MovimientoCaja::create([
                    'turno_caja_id' => $request->turno_caja_id,
                    'tipo'          => 'INGRESO',
                    'concepto'      => 'VENTA_MOSTRADOR',
                    'metodo_pago'   => $metodoPagoCaja,
                    'monto'         => $request->total,
                    'descripcion'   => 'Ticket de venta #' . $venta->id,
                ]);
            }

            foreach ($request->items as $item) {
                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $item['id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_venta'],
                    'subtotal'        => $item['cantidad'] * $item['precio_venta'],
                ]);

                DB::table('producto_sucursal')
                    ->where('producto_id', $item['id'])
                    ->where('sucursal_id', $sucursalId) 
                    ->decrement('cantidad_fisica', $item['cantidad']);
                
                DB::table('movimientos_stock')->insert([
                    'producto_id' => $item['id'],
                    'sucursal_id' => $sucursalId,
                    'user_id' => auth()->id(),
                    'tipo_movimiento' => 'Venta',
                    'cantidad_anterior' => $stockActual->cantidad_fisica,
                    'cantidad_movimiento' => -$item['cantidad'],
                    'cantidad_actual' => $stockActual->cantidad_fisica - $item['cantidad'],
                    'motivo' => "Venta POS #{$venta->id}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Venta exitosa');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancelar(Request $request, Venta $venta)
    {
        $request->validate(['motivo' => 'required|string|max:255']);
        if ($venta->estado === 'Cancelada') return back();

        return DB::transaction(function () use ($venta, $request) {
            $venta->load('turno.caja', 'detalles');
            $sucursalId = $venta->turno->caja->sucursal_id;

            foreach ($venta->detalles as $detalle) {
                DB::table('producto_sucursal')
                    ->where('sucursal_id', $sucursalId)
                    ->where('producto_id', $detalle->producto_id)
                    ->increment('cantidad_fisica', $detalle->cantidad);
            }

            if ($venta->metodo_pago === 'Cuenta Corriente' && $venta->consumidor_id) {
                $cuenta = CuentaCorriente::where('consumidor_id', $venta->consumidor_id)->first();
                if ($cuenta) {
                    $cuenta->decrement('saldo_deudor', $venta->total);
                    MovimientoCuentaCorriente::create([
                        'cuenta_corriente_id' => $cuenta->id,
                        'venta_id'            => $venta->id,
                        'monto'               => $venta->total,
                        'tipo'                => 'abono',
                        'descripcion'         => 'Anulación Venta #' . $venta->id,
                    ]);
                }
            } else {
                $metodoPagoCaja = strtoupper(str_replace(' ', '_', $venta->metodo_pago));
                MovimientoCaja::create([
                    'turno_caja_id' => $venta->turno_caja_id,
                    'tipo'          => 'EGRESO',
                    'concepto'      => 'ANULACION_VENTA',
                    'metodo_pago'   => $metodoPagoCaja,
                    'monto'         => $venta->total,
                    'descripcion'   => 'Anulación de venta #' . $venta->id . ' - Motivo: ' . $request->motivo,
                ]);
            }

            $venta->update(['estado' => 'Cancelada', 'motivo_anulacion' => $request->motivo]);
            return redirect()->back();
        });
    }
}