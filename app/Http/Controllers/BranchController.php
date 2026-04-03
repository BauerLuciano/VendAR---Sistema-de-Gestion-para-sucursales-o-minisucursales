<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index()
    {
        return Inertia::render('Branches/Index', [
            'branches' => Branch::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string',
            'type' => 'nullable|string',
        ]);

        Branch::create($validated);
        
        return redirect()->back()->with('success', 'Sucursal creada exitosamente.');
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $branch->update($validated);
        
        return redirect()->back()->with('success', 'Sucursal actualizada.');
    }

    public function status(Branch $branch)
    {
        // Cambiamos el estado (Baja/Alta lógica) usando tu campo 'is_active'
        $branch->update(['is_active' => !$branch->is_active]);
        
        return redirect()->back()->with('success', 'Estado modificado.');
    }
}