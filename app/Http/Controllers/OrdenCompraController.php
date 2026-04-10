<?php

namespace App\Http\Controllers;

// --- 1. IMPORTACIONES ---
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Sucursal;
use App\Models\Proveedor;
use App\Models\IngresoMercaderia; 
use App\Models\IngresoDetalle;    
use App\Models\Producto;          
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrdenConfirmadaProveedor; // Asegurate de crear este Mailable

class OrdenCompraController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $esJefe = $user->hasRole(['SuperAdmin', 'Administrador Global']);

        $query = OrdenCompra::with(['proveedor', 'sucursal', 'usuario', 'detalles.producto']);

        if (!$esJefe && $user->branch_id) {
            $query->where('sucursal_id', $user->branch_id);
        }

        $ordenes = $query->orderBy('id', 'desc')->get();
        $proveedores = Proveedor::where('estado', true)->get();
        $sucursales = $esJefe ? Sucursal::all() : Sucursal::where('id', $user->branch_id)->get();

        return Inertia::render('OrdenesCompra/Index', [
            'ordenes' => $ordenes,
            'proveedores' => $proveedores,
            'sucursales' => $sucursales
        ]);
    }

    public function generarSugerencias()
    {
        $user = auth()->user();
        $esJefe = $user->hasRole(['SuperAdmin', 'Administrador Global']);
        $sucursalesToProcess = $esJefe ? Sucursal::pluck('id') : [$user->branch_id];

        DB::beginTransaction();
        try {
            foreach ($sucursalesToProcess as $sucId) {
                $productosBajoStock = DB::table('productos')
                    ->join('producto_sucursal', 'productos.id', '=', 'producto_sucursal.producto_id')
                    ->where('producto_sucursal.sucursal_id', $sucId)
                    ->whereNotNull('productos.proveedor_id')
                    ->whereRaw('producto_sucursal.cantidad_fisica <= productos.stock_minimo')
                    ->select('productos.id', 'productos.proveedor_id', 'productos.stock_minimo', 'productos.precio_costo', 'producto_sucursal.cantidad_fisica')
                    ->get();

                $porProveedor = $productosBajoStock->groupBy('proveedor_id');

                foreach ($porProveedor as $proveedorId => $productos) {
                    $orden = OrdenCompra::firstOrCreate(
                        ['sucursal_id' => $sucId, 'proveedor_id' => $proveedorId, 'estado' => 'Sugerida'],
                        ['user_id' => $user->id, 'fecha_emision' => now(), 'total_estimado' => 0, 'observaciones' => 'Generada automáticamente por alerta de stock mínimo.']
                    );

                    $totalEstimado = $orden->total_estimado;

                    foreach ($productos as $prod) {
                        $detalleExiste = OrdenCompraDetalle::where('orden_compra_id', $orden->id)->where('producto_id', $prod->id)->exists();
                        if (!$detalleExiste) {
                            $cantidadPedida = ($prod->stock_minimo * 2) - $prod->cantidad_fisica;
                            if ($cantidadPedida <= 0) $cantidadPedida = 1;
                            $costo = $prod->precio_costo ?? 0; 
                            $subtotal = $cantidadPedida * $costo;

                            OrdenCompraDetalle::create([
                                'orden_compra_id' => $orden->id,
                                'producto_id' => $prod->id,
                                'cantidad_pedida' => $cantidadPedida,
                                'costo_unitario_estimado' => $costo,
                                'subtotal_estimado' => $subtotal
                            ]);
                            $totalEstimado += $subtotal;
                        }
                    }
                    $orden->update(['total_estimado' => $totalEstimado]);
                }
            }
            DB::commit();
            return redirect()->back()->with('exito', 'Órdenes sugeridas generadas.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * PASO INTERMEDIO: Aceptar cotización y avisar al proveedor
     */
    public function confirmarPedido(OrdenCompra $ordenCompra)
    {
        if ($ordenCompra->estado !== 'Cotizada') {
            return redirect()->back()->with('error', 'Solo se pueden confirmar órdenes cotizadas.');
        }

        $ordenCompra->update(['estado' => 'Aprobada']);

        // Enviar Email al proveedor
        $correoDestino = $ordenCompra->proveedor->email ?? 'proveedor@test.com';
        Mail::to($correoDestino)->send(new OrdenConfirmadaProveedor($ordenCompra));

        return redirect()->back()->with('exito', '¡Pedido confirmado! Se le envió un correo al proveedor.');
    }

    /**
     * PASO FINAL: Registrar ingreso real de mercadería y sumar stock
     */
    public function aprobarYRecibir(OrdenCompra $ordenCompra)
{
    if ($ordenCompra->estado !== 'Aprobada') {
        return redirect()->back()->with('error', 'Primero debes confirmar el pedido al proveedor.');
    }

    DB::beginTransaction();
    try {
        $ordenCompra->load('detalles.producto');

        // 1. Crear el Ingreso histórico (Remito)
        $ingreso = IngresoMercaderia::create([
            'sucursal_id'  => $ordenCompra->sucursal_id,
            'proveedor_id' => $ordenCompra->proveedor_id,
            'user_id'      => auth()->id(),
            'fecha_ingreso' => now(),
            'numero_remito' => 'AUTO-OC-' . str_pad($ordenCompra->id, 4, '0', STR_PAD_LEFT),
            'total_costo'   => $ordenCompra->total_estimado,
        ]);

        $alertasInflacion = [];

        foreach ($ordenCompra->detalles as $detalle) {
            // A. Registrar detalle del ingreso
            IngresoDetalle::create([
                'ingreso_mercaderia_id' => $ingreso->id,
                'producto_id'           => $detalle->producto_id,
                'cantidad_recibida'     => $detalle->cantidad_pedida,
                'costo_unitario'        => $detalle->costo_unitario_estimado,
            ]);

            $producto = $detalle->producto;
            $precioAnterior = $producto->precio_costo;
            $nuevoPrecio = $detalle->costo_unitario_estimado;

            // B. LÓGICA DE ACTUALIZACIÓN DE PRECIO (El corazón del cambio)
            // Si el precio es distinto (o si es el primer ingreso y estaba en 0)
            if ($nuevoPrecio != $precioAnterior) {
                
                // Si hubo aumento, lo metemos en la bolsa de alertas para avisarte
                if ($nuevoPrecio > $precioAnterior && $precioAnterior > 0) {
                    $alertasInflacion[] = [
                        'producto' => $producto->nombre,
                        'costo_viejo' => $precioAnterior,
                        'costo_nuevo' => $nuevoPrecio,
                        'porcentaje' => number_format((($nuevoPrecio - $precioAnterior) / $precioAnterior) * 100, 2)
                    ];
                }

                // ACTUALIZAMOS EL COSTO DEL PRODUCTO SIEMPRE
                $producto->update(['precio_costo' => $nuevoPrecio]);
            }

            // C. Actualización de Stock Físico
            $producto->sucursales()->updateExistingPivot($ordenCompra->sucursal_id, [
                'cantidad_fisica' => DB::raw("cantidad_fisica + {$detalle->cantidad_pedida}")
            ]);
        }

        $ordenCompra->update(['estado' => 'Recepcionada']);
        DB::commit();

        // Mandamos los datos a la sesión para que SweetAlert los use
        return redirect()->route('ingresos.index')->with([
            'exito' => 'Mercadería recibida. Precios y Stock actualizados.',
            'alertas_inflacion' => $alertasInflacion
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
    }
}

    public function cambiarEstado(Request $request, OrdenCompra $ordenCompra)
    {
        $validated = $request->validate([
            'estado' => 'required|in:Sugerida,Borrador,Enviada,Cotizada,Aprobada,Recepcionada,Cancelada'
        ]);
        $ordenCompra->update(['estado' => $validated['estado']]);
        return redirect()->back()->with('exito', "Estado actualizado.");
    }

    public function destroy(OrdenCompra $ordenCompra)
    {
        if (in_array($ordenCompra->estado, ['Enviada', 'Recepcionada', 'Cotizada', 'Aprobada'])) {
            return redirect()->back()->with('error', 'No se puede eliminar una orden con actividad.');
        }
        $ordenCompra->delete();
        return redirect()->back()->with('exito', 'Orden eliminada.');
    }
}