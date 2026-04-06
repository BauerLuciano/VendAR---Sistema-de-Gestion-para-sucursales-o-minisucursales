<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TurnoCaja extends Model {
    protected $fillable = ['caja_id', 'user_id', 'saldo_inicial', 'saldo_final', 'fecha_apertura', 'fecha_cierre', 'estado'];
    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function caja() { return $this->belongsTo(Caja::class); }
    public function cajero() { return $this->belongsTo(User::class, 'user_id'); }
    public function ventas() { return $this->hasMany(Venta::class); }
}