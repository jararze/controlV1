<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TruckTrackingHistory extends Model
{
    use HasFactory;

    protected $table = 'truck_tracking_history';

    protected $fillable = [
        'patente', 'planilla', 'latitude', 'longitude', 'velocidad_kmh',
        'direccion', 'geocerca_docks', 'geocerca_track_trace',
        'geocerca_cbn', 'geocerca_ciudades', 'porcentaje_entrega',
        'estado_entrega', 'tiempo_espera_minutos', 'estado_descarga',
        'api_timestamp'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'velocidad_kmh' => 'decimal:2',
        'porcentaje_entrega' => 'decimal:2',
    ];

    // Relaciones
    public function truckTracking()
    {
        return $this->belongsTo(TruckTracking::class, 'patente', 'patente');
    }

    // Scopes
    public function scopeForTruck($query, $patente)
    {
        return $query->where('patente', $patente);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
