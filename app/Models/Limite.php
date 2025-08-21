<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Limite extends Model
{
    use HasFactory;
    protected $table = 'limite';
    protected $guarded = [];

    protected $casts = [
        'FECHA_ALERTA' => 'datetime',
        'fecha_registro' => 'datetime'
    ];

    public function getLatitudAttribute()
    {
        $coords = explode(',', $this->UBICACION);
        return isset($coords[0]) ? floatval($coords[0]) : null;
    }

    public function getLongitudAttribute()
    {
        $coords = explode(',', $this->UBICACION);
        return isset($coords[1]) ? floatval($coords[1]) : null;
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByFecha($query, $fecha)
    {
        return $query->whereDate('fecha_registro', $fecha);
    }
}
