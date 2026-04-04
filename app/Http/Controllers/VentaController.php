<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Producto;
use App\Models\Consumidor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VentaController extends Controller
{
    /**
     * HISTORIAL: Muestra el listado de todas las ventas
     */
    public function index()
    {
        $branchId = auth()->user()->branch_id ?? 1;

        $ventas = Venta::with(['consumidor', 'user', 'detalles.producto'])
            ->where('sucursal_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Ventas/Index', [
            'ventas' => $ventas
        ]);
    }

    /**
     * POS: Muestra la pantalla de la Caja
     */
    public function create()
    {
        $branchId = auth()->user()->branch_id ?? 1;

        return Inertia::render('Pos/Index', [
            'productos' => Producto::where('estado', true)
                ->select('id', 'nombre', 'sku', 'precio_venta', 'imagen')
                ->with(['branch_productos' => function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                }])
                ->get(),
            'clientes' => Consumidor::all(),
        ]);
    }

    /**
     * Procesa la venta final
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:consumidores,id',
            'items' => 'required|array|min:1',
            'metodo_pago' => 'required|string',
            'total_venta' => 'required|numeric',
        ]);

        $consumidor = Consumidor::find($request->cliente_id);

        if ($request->metodo_pago === 'cuenta_corriente') {
            if ($consumidor->id == 1) {
                return back()->withErrors(['error' => 'No puedes fiar a un Consumidor Final. Seleccioná un cliente registrado.']);
            }
            $cuenta = $consumidor->cuentaCorriente;
            if (!$cuenta) {
                return back()->withErrors(['error' => 'Este cliente no tiene una cuenta corriente activa.']);
            }
            if (($cuenta->saldo_deudor + $request->total_venta) > $consumidor->limite_cuenta_corriente) {
                return back()->withErrors(['error' => 'La venta supera el límite de fiado del cliente.']);
            }
        }

        return DB::transaction(function () use ($request, $consumidor) {
            $branchId = auth()->user()->branch_id ?? 1;

            $venta = Venta::create([
                'user_id' => auth()->id(),
                'consumidor_id' => $consumidor->id,
                'sucursal_id' => $branchId,
                'total' => $request->total_venta,
                'metodo_pago' => $request->metodo_pago,
                'estado' => 'activa', 
                'fecha' => now(),
            ]);

            foreach ($request->items as $item) {
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_venta'],
                    'subtotal' => $item['cantidad'] * $item['precio_venta'],
                ]);

                DB::table('branch_producto')
                    ->where('branch_id', $branchId)
                    ->where('producto_id', $item['id'])
                    ->decrement('cantidad_fisica', $item['cantidad']);
            }

            if ($request->metodo_pago === 'cuenta_corriente') {
                $consumidor->cuentaCorriente->increment('saldo_deudor', $request->total_venta);
                $consumidor->cuentaCorriente->update(['fecha_ultimo_movimiento' => now()]);
            }

            return redirect()->route('pos.index')->with('success', 'Venta registrada.');
        });
    }

    /**
     * ANULAR VENTA: Devuelve stock posta y ajusta cuentas.
     */
    public function cancelar(Request $request, Venta $venta)
    {
        $request->validate(['motivo' => 'required|string|max:255']);

        if ($venta->estado === 'anulada') {
            return back()->withErrors(['error' => 'Esta venta ya se encuentra anulada.']);
        }

        return DB::transaction(function () use ($venta, $request) {
            $branchId = $venta->sucursal_id;

            // 1. REVESTIR STOCK POSTA (Suma lo que se vendió de nuevo al inventario)
            foreach ($venta->detalles as $detalle) {
                DB::table('branch_producto')
                    ->where('branch_id', $branchId)
                    ->where('producto_id', $detalle->producto_id)
                    ->increment('cantidad_fisica', $detalle->cantidad);
            }

            // 2. REVERTIR DEUDA
            if ($venta->metodo_pago === 'cuenta_corriente') {
                $consumidor = Consumidor::find($venta->consumidor_id);
                if ($consumidor && $consumidor->cuentaCorriente) {
                    $consumidor->cuentaCorriente->decrement('saldo_deudor', $venta->total);
                }
            }

            // 3. CAMBIAR ESTADO
            $venta->update([
                'estado' => 'anulada',
                'motivo_anulacion' => $request->motivo
            ]);
            
            return redirect()->back()->with('success', 'Venta anulada. El stock volvió al estante.');
        });
    }
}   