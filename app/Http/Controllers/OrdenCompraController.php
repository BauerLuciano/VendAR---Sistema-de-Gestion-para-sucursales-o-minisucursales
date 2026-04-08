<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Sucursal;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $esJefe = $user->hasRole(['SuperAdmin', 'Administrador Global']);

        // Traemos las órdenes con sus relaciones clave
        $query = OrdenCompra::with(['proveedor', 'sucursal', 'usuario', 'detalles.producto']);

        // Seguridad: Si no es jefe, solo ve los pedidos de su sucursal
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

    /**
     * 🚀 EL ALGORITMO ESTRELLA: Genera las OCS (Órdenes de Compra Sugeridas)
     */
    public function generarSugerencias()
    {
        $user = auth()->user();
        $esJefe = $user->hasRole(['SuperAdmin', 'Administrador Global']);

        // Definimos qué sucursales vamos a analizar
        $sucursalesToProcess = $esJefe 
            ? Sucursal::pluck('id') 
            : [$user->branch_id];

        // Usamos una Transacción: si algo falla a la mitad, se cancela todo (ACID)
        DB::beginTransaction();
        
        try {
            foreach ($sucursalesToProcess as $sucId) {
                // 1. Buscamos productos que tengan stock por debajo del mínimo en esta sucursal
                $productosBajoStock = DB::table('productos')
                    ->join('producto_sucursal', 'productos.id', '=', 'producto_sucursal.producto_id')
                    ->where('producto_sucursal.sucursal_id', $sucId)
                    ->whereNotNull('productos.proveedor_id') // Obligatorio: debe tener proveedor asignado
                    ->whereRaw('producto_sucursal.cantidad_fisica <= productos.stock_minimo')
                    ->select('productos.id', 'productos.proveedor_id', 'productos.stock_minimo', 'productos.precio_costo', 'producto_sucursal.cantidad_fisica')
                    ->get();

                // 2. Los agrupamos por Proveedor (El "carrito inteligente")
                $porProveedor = $productosBajoStock->groupBy('proveedor_id');

                foreach ($porProveedor as $proveedorId => $productos) {
                    
                    // 3. Buscamos si ya hay un borrador abierto para no duplicar pedidos
                    $orden = OrdenCompra::firstOrCreate(
                        [
                            'sucursal_id' => $sucId,
                            'proveedor_id' => $proveedorId,
                            'estado' => 'Sugerida' // Buscamos una sugerida pendiente
                        ],
                        [
                            'user_id' => $user->id,
                            'fecha_emision' => now(),
                            'total_estimado' => 0,
                            'observaciones' => 'Generada automáticamente por alerta de stock mínimo.',
                        ]
                    );

                    $totalEstimado = $orden->total_estimado;

                    // 4. Metemos los productos adentro de la orden
                    foreach ($productos as $prod) {
                        // Evitamos meter el mismo producto dos veces en la misma orden
                        $detalleExiste = OrdenCompraDetalle::where('orden_compra_id', $orden->id)
                            ->where('producto_id', $prod->id)
                            ->exists();

                        if (!$detalleExiste) {
                            // Lógica de reposición: Pedimos lo necesario para cubrir el doble del mínimo
                            $cantidadPedida = ($prod->stock_minimo * 2) - $prod->cantidad_fisica;
                            if ($cantidadPedida <= 0) $cantidadPedida = 1; // Mínimo 1 por las dudas

                            // Asumimos que la columna se llama 'precio_costo'. ¡Ajustalo si se llama distinto en tu BD!
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

                    // Actualizamos la plata total que creemos que nos va a costar el pedido
                    $orden->update(['total_estimado' => $totalEstimado]);
                }
            }

            DB::commit(); // Confirmamos los cambios en la BD
            return redirect()->back()->with('success', 'Algoritmo ejecutado: Se generaron las Órdenes Sugeridas.');

        } catch (\Exception $e) {
            DB::rollBack(); // Si explotó algo, damos marcha atrás
            return redirect()->back()->with('error', 'Error al generar OCS: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar el estado de la Orden (De Sugerida -> Enviada -> Cancelada)
     */
    public function cambiarEstado(Request $request, OrdenCompra $ordenCompra)
    {
        $validated = $request->validate([
            'estado' => 'required|in:Sugerida,Borrador,Enviada,Recepcionada,Cancelada'
        ]);

        $ordenCompra->update(['estado' => $validated['estado']]);
        
        return redirect()->back()->with('success', "La orden pasó a estado: {$validated['estado']}");
    }

    /**
     * Eliminar la Orden (Soft Delete)
     */
    public function destroy(OrdenCompra $ordenCompra)
    {
        // Solo dejamos borrar si no fue enviada aún
        if (in_array($ordenCompra->estado, ['Enviada', 'Recepcionada'])) {
            return redirect()->back()->with('error', 'No podés eliminar una orden que ya está en camino o fue recibida.');
        }

        $ordenCompra->delete();
        return redirect()->back()->with('success', 'Orden de Compra eliminada.');
    }
}