<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exceso extends Model
{
    use HasFactory;

    protected $table = 'excesos';
    protected $guarded = [];

    protected $casts = [
        'FECHA_EXCESO' => 'datetime',
        'FECHA_RESTITUCION' => 'datetime',
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

    public function getDuracionFormateadaAttribute()
    {
        $segundos = $this->DURACION_SEG;
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segs = $segundos % 60;

        return sprintf("%02d:%02d:%02d", $horas, $minutos, $segs);
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
