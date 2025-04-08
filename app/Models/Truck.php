<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_llegada' => 'date',
        'fecha_salida' => 'date',
        'fecha_orden' => 'date',
    ];
}
