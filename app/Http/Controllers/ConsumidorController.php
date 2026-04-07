<?php

namespace App\Http\Controllers;

use App\Models\Consumidor;
use App\Models\TurnoCaja;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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
            'nombre' => 'required|string|max:50|regex:/^[^0-9]+$/',
            'apellido' => 'required|string|max:50|regex:/^[^0-9]+$/',
            'documento' => ['nullable', 'string', 'regex:/^\d{7,8}$/', 'unique:consumidores,documento'],
            'email' => 'nullable|email|max:255|unique:consumidores,email',
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

    // ========================================================
    // NUEVO: MÉTODO PARA COBRAR DEUDA E IMPACTAR EN CAJA
    // ========================================================
    public function cobrarDeuda(Request $request, Consumidor $consumidor)
    {
        $request->validate([
            'monto' => 'required|numeric|min:1',
            'metodo_pago' => 'required|string|in:EFECTIVO,MERCADO_PAGO,TRANSFERENCIA'
        ]);

        $cuenta = $consumidor->cuentaCorriente;

        // Validamos que exista la cuenta y que el monto a cobrar no sea mayor a la deuda
        if (!$cuenta || $cuenta->saldo_deudor < $request->monto) {
            return back()->withErrors(['monto' => 'El monto ingresado es mayor a la deuda actual del cliente.']);
        }

        DB::beginTransaction();

        try {
            // 1. Descontamos el dinero de la deuda del cliente
            $cuenta->saldo_deudor -= $request->monto;
            $cuenta->fecha_ultimo_movimiento = now();
            $cuenta->save();

            // 2. Buscamos si el usuario actual tiene una caja abierta
            $user = auth()->user();
            $turno = TurnoCaja::where('user_id', $user->id)
                        ->where('estado', 'Abierto')
                        ->first();

            // 3. Si tiene caja abierta, inyectamos el movimiento de ingreso de plata
            if ($turno) {
                MovimientoCaja::create([
                    'turno_caja_id' => $turno->id,
                    'tipo'          => 'INGRESO',
                    'concepto'      => 'COBRO_CUENTA_CORRIENTE',
                    'metodo_pago'   => $request->metodo_pago,
                    'monto'         => $request->monto,
                    'descripcion'   => 'Pago fiado: ' . $consumidor->nombre . ' ' . $consumidor->apellido
                ]);
            }

            DB::commit();
            return back()->with('success', 'Cobro registrado exitosamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['monto' => 'Error de BD al procesar el pago: ' . $e->getMessage()]);
        }
    }
}