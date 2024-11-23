<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Argus extends Model
{
    protected $guarded = [];

    protected $casts = [
        'dia' => 'date',
        'hora_alarma' => 'datetime',
        'fecha_registro' => 'datetime',
    ];
}
