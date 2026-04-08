<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'proveedor_id', 'sucursal_id', 'user_id', 'nro_comprobante', 
        'fecha_emision', 'fecha_entrega_esperada', 'estado', 
        'total_estimado', 'observaciones'
    ];

    public function proveedor() { return $this->belongsTo(Proveedor::class); }
    public function sucursal() { return $this->belongsTo(Sucursal::class); }
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
    
    // Una orden tiene muchos detalles
    public function detalles() { return $this->hasMany(OrdenCompraDetalle::class); }
}