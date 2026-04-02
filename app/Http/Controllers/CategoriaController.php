<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str; 
use Inertia\Inertia;

class CategoriaController extends Controller
{
    public function index()
    {
        return Inertia::render('Categorias/Index', [
            'categorias' => Categoria::orderBy('id', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validados = $request->validate([
            'nombreCategoria' => 'required|string|max:255|unique:categorias,nombreCategoria',
        ]);

        $validados['slug'] = Str::slug($request->nombreCategoria);
        $validados['estado'] = true;

        Categoria::create($validados);

        return redirect()->back();
    }

    public function update(Request $request, Categoria $categoria)
    {
        $validados = $request->validate([
            'nombreCategoria' => 'required|string|max:255|unique:categorias,nombreCategoria,' . $categoria->id,
        ]);

        $validados['slug'] = Str::slug($request->nombreCategoria);

        $categoria->update($validados);

        return redirect()->back();
    }

    public function status(Categoria $categoria)
    {
        $categoria->update([
            'estado' => !$categoria->estado
        ]);

        return redirect()->back();
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->update(['estado' => false]);
        
        return redirect()->back();
    }
}