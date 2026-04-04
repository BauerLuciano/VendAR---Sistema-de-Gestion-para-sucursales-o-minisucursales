<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consumidor extends Model
{
    use HasFactory;

    protected $table = 'consumidores';

    protected $fillable = [
        'nombre',
        'apellido',
        'documento',
        'email',
        'telefono',
        'direccion',
        'limite_cuenta_corriente',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'limite_cuenta_corriente' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::created(function ($consumidor) {
            $consumidor->cuentaCorriente()->create([
                'saldo_deudor' => 0,
                'estado' => true,
            ]);
        });
    }

    public function cuentaCorriente()
    {
        return $this->hasOne(CuentaCorriente::class);
    }
}