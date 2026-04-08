<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CajaController extends Controller
{
    public function index()
{
    $user = auth()->user();
    
    // 1. Verificamos si es un "Jefe" (SuperAdmin o Admin Global)
    $esJefe = $user->hasRole(['SuperAdmin', 'Administrador Global']);
    
    $query = Caja::with('sucursal');
    
    if (!$esJefe && $user->branch_id) {
        $query->where('sucursal_id', $user->branch_id);
    }
    $cajas = $query->orderBy('id', 'desc')->get();

    $sucursales = $esJefe 
        ? Sucursal::all() 
        : Sucursal::where('id', $user->branch_id)->get();

    return Inertia::render('Cajas/Index', [
        'cajas' => $cajas,
        'sucursales' => $sucursales
    ]);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'sucursal_id' => 'required|exists:sucursales,id',
        ]);

        Caja::create($validated);
        return redirect()->back()->with('success', 'Caja creada.');
    }

    public function update(Request $request, Caja $caja)
    {
        $validated = $request->validate(['nombre' => 'required|string|max:255']);
        $caja->update($validated);
        return redirect()->back()->with('success', 'Caja actualizada.');
    }

    public function toggleEstado(Caja $caja)
    {
        $caja->update(['estado' => !$caja->estado]);
        return redirect()->back()->with('success', 'Estado modificado.');
    }

    public function destroy(Caja $caja)
    {
        $caja->delete();
        return redirect()->back()->with('success', 'Caja eliminada.');
    }
}