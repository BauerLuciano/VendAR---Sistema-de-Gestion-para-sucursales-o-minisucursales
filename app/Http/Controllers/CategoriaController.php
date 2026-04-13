<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str; 
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $estado = $request->input('estado', 'all');

        $categorias = Categoria::when($search, function ($query, $search) {
                $query->where('nombreCategoria', 'LIKE', "%{$search}%");
            })
            ->when($estado !== 'all', function ($query) use ($estado) {
                $query->where('estado', $estado === 'activos' ? true : false);
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Categorias/Index', [
            'categorias' => $categorias,
            'filtros' => $request->only(['search', 'estado'])
        ]);
    }

    public function store(Request $request)
    {
        $validados = $request->validate([
            'nombreCategoria' => 'required|string|max:100|unique:categorias,nombreCategoria',
            'descripcion'     => 'nullable|string|max:500',
        ]);

        $validados['slug'] = Str::slug($request->nombreCategoria);
        $validados['estado'] = true;

        Categoria::create($validados);

        return redirect()->back()->with('success', 'Categoría creada.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        $validados = $request->validate([
            'nombreCategoria' => ['required', 'string', 'max:100', Rule::unique('categorias')->ignore($categoria->id)],
            'descripcion'     => 'nullable|string|max:500', 
        ]);

        $validados['slug'] = Str::slug($request->nombreCategoria);

        $categoria->update($validados);

        return redirect()->back()->with('success', 'Categoría actualizada.');
    }

    public function status(Categoria $categoria)
    {
        $categoria->update(['estado' => !$categoria->estado]);
        return redirect()->back()->with('success', 'Estado modificado.');
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->update(['estado' => false]);
        return redirect()->back();
    }
}