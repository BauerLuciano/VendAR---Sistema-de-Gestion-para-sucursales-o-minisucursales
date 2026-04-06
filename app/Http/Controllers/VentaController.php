<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\TurnoCaja;
use App\Models\Consumidor;
use App\Models\CuentaCorriente;
use App\Models\MovimientoCuentaCorriente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VentaController extends Controller
{
    public function index()
    {
        $sucursalId = auth()->user()->branch_id ?? 1;
        $ventas = Venta::with(['consumidor', 'turno.cajero', 'turno.caja', 'detalles.producto'])
            ->whereHas('turno.caja', function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            })
            ->orderBy('created_at', 'desc')
            ->get(); 

        return Inertia::render('Ventas/Index', [
            'ventas' => $ventas
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

            // 1. Crear la Venta
            $venta = Venta::create([
                'turno_caja_id' => $request->turno_caja_id,
                'consumidor_id' => $request->consumidor_id,
                'metodo_pago'   => $request->metodo_pago,
                'total'         => $request->total,
                'estado'        => 'Completada',
            ]);

            // 2. Lógica de Fiado (Cuenta Corriente)
            if ($request->metodo_pago === 'Cuenta Corriente') {
                $cuenta = CuentaCorriente::firstOrCreate(
                    ['consumidor_id' => $request->consumidor_id],
                    ['saldo_deudor' => 0]
                );
                
                $cuenta->increment('saldo_deudor', $request->total);
                
                // Usamos el modelo para que Laravel encuentre la tabla solo
                MovimientoCuentaCorriente::create([
                    'cuenta_corriente_id' => $cuenta->id,
                    'venta_id'            => $venta->id,
                    'monto'               => $request->total,
                    'tipo'                => 'cargo',
                    'descripcion'         => 'Compra en POS',
                ]);
            }

            // 3. Procesar Stock
            $turno = TurnoCaja::with('caja')->findOrFail($request->turno_caja_id);
            $sucursalId = $turno->caja->sucursal_id;

            foreach ($request->items as $item) {
                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $item['id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_venta'],
                    'subtotal'        => $item['cantidad'] * $item['precio_venta'],
                ]);

                // ATENCIÓN: Usamos la tabla de la migración: 'producto_sucursal'
                // Y la columna 'cantidad_fisica'
                DB::table('producto_sucursal')
                    ->where('producto_id', $item['id'])
                    ->where('sucursal_id', $sucursalId) 
                    ->decrement('cantidad_fisica', $item['cantidad']);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Venta exitosa');

        } catch (\Exception $e) {
            DB::rollBack();
            // Log para debug
            \Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Falla en BD: ' . $e->getMessage()]);
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
            }

            $venta->update(['estado' => 'Cancelada', 'motivo_anulacion' => $request->motivo]);
            return redirect()->back();
        });
    }
}