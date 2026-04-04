<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index()
    {
        return Inertia::render('Productos/Index', [
            'productos' => Producto::with(['categoria', 'marca'])->orderBy('id', 'desc')->get(),
            'categorias' => Categoria::all(), 
            'marcas' => Marca::all(),         
        ]);
    }

    public function store(Request $request)
    {
        $validados = $request->validate([
            'nombre'        => 'required|string|max:255',
            'codigo_barras' => 'required|string|max:50|unique:productos,codigo_barras',
            'categoria_id'  => 'required|exists:categorias,id',
            'marca_id'      => 'required|exists:marcas,id',
            'unidad_medida' => 'required|in:Unidad,Kg,Gramos',
            'es_retornable' => 'boolean',
            'precio_costo'  => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'descripcion'   => 'nullable|string',
            'imagen'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 
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
            'codigo_barras' => ['required', 'string', 'max:50', Rule::unique('productos')->ignore($producto->id)],
            'categoria_id'  => 'required|exists:categorias,id',
            'marca_id'      => 'required|exists:marcas,id',
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
}