<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

    class Comercio extends Model
    {
        protected $casts = [
        'modulos_habilitados' => 'array',
        'vencimiento_pago' => 'date',
];
}
