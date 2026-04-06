<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\TurnoCaja;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PosController extends Controller
{
    // Esta es la puerta de entrada al POS
   public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Buscamos si el usuario ya tiene un turno abierto
        $turnoAbierto = TurnoCaja::where('user_id', $user->id)
            ->where('estado', 'Abierto')
            ->first();

        // 2. Si ya tiene turno, lo mandamos a vender (A la Terminal que acabamos de arreglar)
        if ($turnoAbierto) {
            $sucursalId = $user->branch_id ?? 1;

            return Inertia::render('Pos/Terminal', [
                'turno' => $turnoAbierto->load('caja.sucursal'),
                // Mandamos los productos con stock en esa sucursal
                'productos' => \App\Models\Producto::where('estado', true)
                    ->select('id', 'nombre', 'codigo_barras', 'precio_venta', 'imagen')
                    ->with(['sucursales' => function($q) use ($sucursalId) {
                        $q->where('sucursal_id', $sucursalId);
                    }])
                    ->get(),
                // Mandamos los clientes por si quiere fiar
                'clientes' => \App\Models\Consumidor::all()
            ]);
        }

        // 3. Si NO tiene turno... (El resto del código de AperturaTurno queda igual)
        $cajasDisponibles = Caja::where('sucursal_id', $user->branch_id ?? 1)
            ->where('estado', true)
            ->get();

        return Inertia::render('Pos/AperturaTurno', [
            'cajas' => $cajasDisponibles
        ]);
    }

    // Este método procesa el formulario de "Abrir Caja"
    public function abrirTurno(Request $request)
    {
        $request->validate([
            'caja_id' => 'required|exists:cajas,id',
            'saldo_inicial' => 'required|numeric|min:0',
        ]);

        // Verificamos por seguridad que nadie más esté usando esa caja
        $cajaEnUso = TurnoCaja::where('caja_id', $request->caja_id)
            ->where('estado', 'Abierto')
            ->exists();

        if ($cajaEnUso) {
            return redirect()->back()->withErrors([
                'caja_id' => 'Esta caja ya está siendo utilizada por otro cajero.'
            ]);
        }

        // ¡Abrimos el turno!
        TurnoCaja::create([
            'caja_id' => $request->caja_id,
            'user_id' => auth()->id(),
            'saldo_inicial' => $request->saldo_inicial,
            'fecha_apertura' => Carbon::now(),
            'estado' => 'Abierto',
        ]);

        return redirect()->route('pos.index')->with('success', 'Turno abierto correctamente. ¡Buenas ventas!');
    }
}