<?php

namespace App\Http\Controllers;

use App\Models\Consumidor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ConsumidorController extends Controller
{
    public function index()
    {
        return Inertia::render('Consumidores/Index', [
            'consumidores' => Consumidor::with('cuentaCorriente')->orderBy('id', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // max:50 y regex para que NO contenga números
            'nombre' => 'required|string|max:50|regex:/^[^0-9]+$/',
            'apellido' => 'required|string|max:50|regex:/^[^0-9]+$/',
            // DNI argentino (7 a 8 dígitos)
            'documento' => ['nullable', 'string', 'regex:/^\d{7,8}$/', 'unique:consumidores,documento'],
            'email' => 'nullable|email|max:255|unique:consumidores,email',
            // Solo números y máximo 15 caracteres
            'telefono' => 'nullable|string|max:15|regex:/^\d+$/',
            'direccion' => 'nullable|string|max:255',
            'limite_cuenta_corriente' => 'required|numeric|min:0',
            'estado' => 'boolean',
        ], [
            'nombre.regex' => 'El nombre no puede contener números.',
            'apellido.regex' => 'El apellido no puede contener números.',
            'documento.regex' => 'El documento debe tener entre 7 y 8 números.',
            'telefono.regex' => 'El teléfono solo puede contener números.',
        ]);

        Consumidor::create($validated);
        
        return redirect()->back()->with('success', 'Cliente registrado exitosamente.');
    }

    public function update(Request $request, Consumidor $consumidor)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|regex:/^[^0-9]+$/',
            'apellido' => 'required|string|max:50|regex:/^[^0-9]+$/',
            'documento' => ['nullable', 'string', 'regex:/^\d{7,8}$/', Rule::unique('consumidores')->ignore($consumidor->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('consumidores')->ignore($consumidor->id)],
            'telefono' => ['nullable', 'string', 'max:15', 'regex:/^\d+$/'],
            'direccion' => 'nullable|string|max:255',
            'limite_cuenta_corriente' => 'required|numeric|min:0',
            'estado' => 'boolean',
        ], [
            'nombre.regex' => 'El nombre no puede contener números.',
            'apellido.regex' => 'El apellido no puede contener números.',
            'documento.regex' => 'El documento debe tener entre 7 y 8 números.',
            'telefono.regex' => 'El teléfono solo puede contener números.',
        ]);

        $consumidor->update($validated);
        
        return redirect()->back()->with('success', 'Datos del cliente actualizados.');
    }
}