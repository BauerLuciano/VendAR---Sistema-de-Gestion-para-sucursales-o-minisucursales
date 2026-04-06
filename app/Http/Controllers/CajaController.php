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
        
        $query = Caja::with('sucursal');
        if ($user->branch_id) {
            $query->where('sucursal_id', $user->branch_id);
        }

        return Inertia::render('Cajas/Index', [
            'cajas' => $query->orderBy('id', 'desc')->get(),
            'sucursales' => $user->branch_id ? Sucursal::where('id', $user->branch_id)->get() : Sucursal::all()
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