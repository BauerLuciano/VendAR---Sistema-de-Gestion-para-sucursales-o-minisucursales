<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GlobalAdminController extends Controller
{
    /**
     * Lista todos los comercios (Kioscos/Mercados) registrados en el sistema
     */
    public function index()
    {
        return Inertia::render('AdminGlobal/Comercios/Index', [
            'comercios' => Comercio::all(),
            // Definimos los módulos disponibles en el sistema para poder tildarlos
            'modulosDisponibles' => [
                ['id' => 'pos', 'nombre' => 'Punto de Venta Base'],
                ['id' => 'inventario', 'nombre' => 'Gestión de Stock Avanzada'],
                ['id' => 'cuentas_corrientes', 'nombre' => 'Cuentas Corrientes (Fiados)'],
                ['id' => 'proveedores', 'nombre' => 'Gestión de Proveedores'],
                ['id' => 'auditoria', 'nombre' => 'Auditoría de Caja y Stock'],
            ]
        ]);
    }

    /**
     * Crea un nuevo cliente (Comercio)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'plan' => 'required|in:basico,pro,premium',
            'limite_sucursales' => 'required|integer|min:1',
            'vencimiento_pago' => 'nullable|date',
        ]);

        // Al crear, le asignamos por defecto el POS base
        $validated['modulos_habilitados'] = ['pos' => true];
        $validated['slug'] = str($request->nombre)->slug();

        Comercio::create($validated);

        return redirect()->back()->with('exito', 'Comercio registrado con éxito.');
    }

    /**
     * La función "mágica": Actualiza qué puede hacer el comercio y su estado
     */
    public function update(Request $request, Comercio $comercio)
    {
        $request->validate([
            'status' => 'required|in:activo,suspendido,trial',
            'modulos_habilitados' => 'required|array',
            'plan' => 'required|string',
        ]);

        $comercio->update([
            'status' => $request->status,
            'plan' => $request->plan,
            'modulos_habilitados' => $request->modulos_habilitados,
            'vencimiento_pago' => $request->vencimiento_pago,
            'limite_sucursales' => $request->limite_sucursales,
        ]);

        return redirect()->back()->with('exito', 'Configuración de comercio actualizada.');
    }
}