<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumidor_id', 
        'sucursal_id', 
        'user_id', 
        'total', 
        'metodo_pago', 
        'fecha',
        'estado',
        'motivo_anulacion'
    ];

    public function consumidor() {
        return $this->belongsTo(Consumidor::class); 
    }
    
    public function detalles() {
        return $this->hasMany(VentaDetalle::class); 
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
    }
}