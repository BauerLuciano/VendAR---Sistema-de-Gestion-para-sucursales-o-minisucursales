<?php

namespace App\Jobs;

use App\Models\Producto;
use App\Models\OrdenCompraSugerida;
use App\Models\OrdenCompraSugeridaDetalle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerarOrdenesCompraSugeridas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // 1. Buscamos productos que, sumando el stock de TODAS las sucursales, no lleguen al mínimo
        // Los agrupamos por proveedor para no mandarle 20 mails al mismo tipo
        $productosFaltantes = Producto::where('estado', true)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('branch_producto')
                    ->whereColumn('branch_producto.producto_id', 'productos.id')
                    ->having(DB::raw('SUM(cantidad_fisica)'), '<=', DB::raw('productos.stock_minimo'))
                    ->groupBy('producto_id');
            })
            ->get()
            ->groupBy('supplier_id');

        foreach ($productosFaltantes as $supplierId => $items) {
            
            // 2. Creamos la "Cabecera" de la orden para este proveedor
            $orden = OrdenCompraSugerida::create([
                'supplier_id' => $supplierId,
                'estado' => 'pendiente',
                'total_estimado' => 0
            ]);

            $totalOrden = 0;

            foreach ($items as $item) {
                // Sugerimos comprar el doble del stock mínimo para estar tranquilos
                $cantidadAComprar = $item->stock_minimo * 2; 

                // 3. Creamos el renglón del "Detalle"
                OrdenCompraSugeridaDetalle::create([
                    'orden_compra_sugerida_id' => $orden->id,
                    'producto_id' => $item->id,
                    'cantidad_sugerida' => $cantidadAComprar,
                    'precio_costo_momento' => $item->precio_costo
                ]);

                $totalOrden += ($cantidadAComprar * $item->precio_costo);
            }

            // 4. Actualizamos el precio final de la orden
            $orden->update(['total_estimado' => $totalOrden]);
        }
    }
}