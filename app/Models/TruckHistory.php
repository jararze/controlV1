<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TruckHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fecha_salida' => 'date',
        'changed_at' => 'datetime',
        'original_data' => 'array',
    ];

    public function getOriginalValue($key)
    {
        return data_get($this->original_data, $key);
    }
}
