<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Marca extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombreMarca',
        'slug',
        'imagen',
        'estado',
    ];

    protected $appends = ['url_imagen'];

    public function getUrlImagenAttribute()
    {
        return $this->imagen ? Storage::url($this->imagen) : null;
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'marca_id');
    }
}