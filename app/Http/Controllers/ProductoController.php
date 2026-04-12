<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Proveedor;
use App\Models\Sucursal; // <-- Importamos Sucursal para el modal de ajuste
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // <-- Importamos DB para las transacciones
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index()
    {
        return Inertia::render('Productos/Index', [
            // Eager loading: Traemos sucursales y también el proveedor
            'productos' => Producto::with(['categoria', 'marca', 'sucursales', 'proveedor'])->orderBy('id', 'desc')->get(),
            'categorias' => Categoria::all(), 
            'marcas' => Marca::all(),        
            'proveedores' => Proveedor::where('estado', true)->get(),
            'sucursales' => Sucursal::all() // <-- Pasamos sucursales para poder hacer ajustes manuales
        ]);
    }

    public function store(Request $request)
    {
        $validados = $request->validate([
            'nombre'        => 'required|string|max:255',
            'codigo_barras' => 'required|string|min:8|max:14|regex:/^[0-9]+$/|unique:productos,codigo_barras',
            'categoria_id'  => 'required|exists:categorias,id',
            'marca_id'      => 'required|exists:marcas,id',
            'proveedor_id'  => 'required|exists:proveedores,id',
            'unidad_medida' => 'required|in:Unidad,Kg,Gramos',
            'es_retornable' => 'boolean',
            'precio_costo'  => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'descripcion'   => 'nullable|string',
            'imagen'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 
        ], [
            'codigo_barras.regex' => 'El código de barras solo puede contener números.',
            'codigo_barras.min' => 'El código debe tener al menos 8 números.',
            'codigo_barras.max' => 'El código no puede superar los 14 números.',
        ]);

        if ($request->hasFile('imagen')) {
            $validados['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $validados['estado'] = true; 

        Producto::create($validados);

        return redirect()->back()->with('success', 'Producto registrado correctamente.');
    }

    public function update(Request $request, Producto $producto)
    {
        $validados = $request->validate([
            'nombre'        => 'required|string|max:255',
            'codigo_barras' => ['required', 'string', 'min:8', 'max:14', 'regex:/^[0-9]+$/', Rule::unique('productos')->ignore($producto->id)],
            'categoria_id'  => 'required|exists:categorias,id',
            'marca_id'      => 'required|exists:marcas,id',
            'proveedor_id'  => 'required|exists:proveedores,id',
            'unidad_medida' => 'required|in:Unidad,Kg,Gramos',
            'es_retornable' => 'boolean',
            'precio_costo'  => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'descripcion'   => 'nullable|string',
            'imagen'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $validados['imagen'] = $request->file('imagen')->store('productos', 'public');
        } else {
            unset($validados['imagen']);
        }

        $producto->update($validados);

        return redirect()->back()->with('success', 'Producto actualizado correctamente.');
    }

    public function status(Producto $producto)
    {
        $producto->update(['estado' => !$producto->estado]);
        return redirect()->back()->with('success', 'Estado modificado.');
    }

    /**
     * 🚀 NUEVO: AJUSTE MANUAL DE STOCK
     */
    public function ajustarStock(Request $request, Producto $producto)
    {
        $validados = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'tipo_ajuste' => 'required|in:Sumar,Restar',
            'cantidad'    => 'required|numeric|min:0.001',
            'motivo'      => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // 1. Buscamos el stock actual del producto en la sucursal específica
            $pivot = DB::table('producto_sucursal')
                ->where('producto_id', $producto->id)
                ->where('sucursal_id', $validados['sucursal_id'])
                ->first();

            $cantidadAnterior = $pivot ? $pivot->cantidad_fisica : 0;
            $cantidadMovimiento = $validados['tipo_ajuste'] === 'Sumar' ? $validados['cantidad'] : -$validados['cantidad'];
            $cantidadActual = $cantidadAnterior + $cantidadMovimiento;

            // Evitamos stock negativo
            if ($cantidadActual < 0) {
                return redirect()->back()->with('error', 'El ajuste no puede dejar el stock físico en negativo.');
            }

            // 2. Actualizamos o Creamos el registro en la tabla pivote de stock
            if ($pivot) {
                DB::table('producto_sucursal')
                    ->where('producto_id', $producto->id)
                    ->where('sucursal_id', $validados['sucursal_id'])
                    ->update(['cantidad_fisica' => $cantidadActual]);
            } else {
                DB::table('producto_sucursal')->insert([
                    'producto_id' => $producto->id,
                    'sucursal_id' => $validados['sucursal_id'],
                    'cantidad_fisica' => $cantidadActual,
                    'cantidad_reservada' => 0
                ]);
            }

            // 3. GUARDAMOS LA AUDITORÍA EN EL "LIBRO MAYOR"
            DB::table('movimientos_stock')->insert([
                'producto_id' => $producto->id,
                'sucursal_id' => $validados['sucursal_id'],
                'user_id' => auth()->id(),
                'tipo_movimiento' => 'Ajuste Manual',
                'cantidad_anterior' => $cantidadAnterior,
                'cantidad_movimiento' => $cantidadMovimiento,
                'cantidad_actual' => $cantidadActual,
                'motivo' => $validados['motivo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            
            // Usamos "exito" para que lo agarre SweetAlert2 en el frontend
            return redirect()->back()->with('exito', 'Stock ajustado correctamente. El movimiento quedó registrado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al procesar el ajuste de stock: ' . $e->getMessage());
        }
    }

    /**
     * 🚀 NUEVO: TRAER AUDITORÍA PARA EL MODAL
     */
    public function auditoria(Producto $producto)
    {
        // Traemos todo el historial de la BD uniendo con usuarios y sucursales
        $movimientos = DB::table('movimientos_stock')
            ->join('users', 'movimientos_stock.user_id', '=', 'users.id')
            ->join('sucursales', 'movimientos_stock.sucursal_id', '=', 'sucursales.id')
            ->where('producto_id', $producto->id)
            ->select('movimientos_stock.*', 'users.name as usuario', 'sucursales.nombre as sucursal')
            ->orderBy('movimientos_stock.created_at', 'desc')
            ->get();

        // Devolvemos en formato JSON para que Vue/Axios lo lea rápido
        return response()->json($movimientos);
    }
}