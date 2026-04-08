<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\PreOrdenProveedor;

class ReposicionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $esJefe = $user->hasRole(['SuperAdmin', 'Administrador Global']);
        
        // Si es jefe, que vea por defecto su sucursal o la primera. Si es cajero, la suya.
        $sucursalId = $esJefe && $user->branch_id ? $user->branch_id : ($user->branch_id ?? Sucursal::first()->id);

        // 1. Buscamos TODOS los productos que están por debajo del mínimo en esa sucursal
        $faltantes = DB::table('productos')
            ->join('producto_sucursal', 'productos.id', '=', 'producto_sucursal.producto_id')
            ->where('producto_sucursal.sucursal_id', $sucursalId)
            ->whereRaw('producto_sucursal.cantidad_fisica <= productos.stock_minimo')
            ->select(
                'productos.id', 
                'productos.nombre', 
                'productos.codigo_barras',
                'productos.proveedor_id', // El proveedor por defecto
                'productos.stock_minimo', 
                'productos.precio_costo',
                'producto_sucursal.cantidad_fisica'
            )->get();

        return Inertia::render('Reposicion/Index', [
            'faltantes' => $faltantes,
            'proveedores' => Proveedor::where('estado', true)->get(),
            'sucursalActual' => Sucursal::find($sucursalId)
        ]);
    }

    public function generarPreOrdenes(Request $request)
    {
        $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.proveedor_id' => 'required|exists:proveedores,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_costo' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $sucursalId = $user->branch_id ?? Sucursal::first()->id;

        DB::beginTransaction();

        try {
            // Agrupamos los productos seleccionados por el PROVEEDOR que eligió el usuario en la tabla
            $porProveedor = collect($request->productos)->groupBy('proveedor_id');

            foreach ($porProveedor as $proveedorId => $items) {
                // Creamos la "Pre-Orden" (Pendiente de Cotización)
                $orden = OrdenCompra::create([
                    'sucursal_id' => $sucursalId,
                    'proveedor_id' => $proveedorId,
                    'user_id' => $user->id,
                    'estado' => 'Borrador', // Acá lo dejamos en Borrador hasta que armemos lo del Mail
                    'fecha_emision' => now(),
                    'total_estimado' => 0,
                    'observaciones' => 'Solicitud de reposición inteligente.',
                ]);

                $total = 0;

                foreach ($items as $item) {
                    $subtotal = $item['cantidad'] * $item['precio_costo'];
                    
                    OrdenCompraDetalle::create([
                        'orden_compra_id' => $orden->id,
                        'producto_id' => $item['producto_id'],
                        'cantidad_pedida' => $item['cantidad'],
                        'costo_unitario_estimado' => $item['precio_costo'],
                        'subtotal_estimado' => $subtotal
                    ]);

                    $total += $subtotal;
                }
				$correoDestino = $orden->proveedor->email ?? 'proveedor@test.com';
                Mail::to($correoDestino)->send(new PreOrdenProveedor($orden));
                $orden->update(['total_estimado' => $total]);
            }

            DB::commit();
            return redirect()->route('ordenes-compra.index')->with('success', '¡Pre-Órdenes generadas exitosamente!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Hubo un error: ' . $e->getMessage());
        }
    }
}