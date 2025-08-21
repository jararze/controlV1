<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class TruckTracking extends Model
{
    use HasFactory;

    protected $table = 'truck_tracking';

    protected $fillable = [
        'cod', 'deposito_origen', 'cod_destino', 'deposito_destino',
        'planilla', 'patente', 'fecha_salida', 'hora_salida',
        'fecha_llegada', 'hora_llegada', 'cod_producto', 'producto',
        'status', 'salida', 'latitude', 'longitude', 'velocidad_kmh',
        'direccion', 'api_timestamp', 'geocerca_docks', 'geocerca_track_trace',
        'geocerca_cbn', 'geocerca_ciudades', 'porcentaje_entrega',
        'estado_entrega', 'inicio_espera_descarga', 'tiempo_espera_minutos',
        'estado_descarga', 'primera_deteccion'
    ];

    protected $casts = [
        'fecha_salida' => 'date',
        'fecha_llegada' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'velocidad_kmh' => 'decimal:2',
        'porcentaje_entrega' => 'decimal:2',
        'inicio_espera_descarga' => 'datetime',
        'primera_deteccion' => 'datetime',
    ];

    // Relaciones
    public function history()
    {
        return $this->hasMany(TruckTrackingHistory::class, 'patente', 'patente');
    }

    public function latestHistory()
    {
        return $this->hasOne(TruckTrackingHistory::class, 'patente', 'patente')
            ->latest('created_at');
    }

    // Scopes
    public function scopeInTransit($query)
    {
        return $query->where('status', 'SALIDA');
    }

    public function scopeWaitingForDischarge($query)
    {
        return $query->where('tiempo_espera_minutos', '>', 0);
    }

    public function scopeInGeocerca($query, $geocerca)
    {
        $column = 'geocerca_' . strtolower(str_replace(' ', '_', $geocerca));
        return $query->where($column, '!=', 'NO');
    }

    public function scopeCriticalWaiting($query, $hours = 48)
    {
        return $query->where('tiempo_espera_minutos', '>=', $hours * 60);
    }

    // Accessors
    public function getTiempoEsperaHorasAttribute()
    {
        return round($this->tiempo_espera_minutos / 60, 2);
    }

    public function getAlertLevelAttribute()
    {
        $horas = $this->tiempo_espera_horas;

        if ($horas >= 48) return 'CRITICAL';
        if ($horas >= 8) return 'WARNING';
        if ($horas >= 4) return 'ATTENTION';

        return 'NORMAL';
    }

    public function getIsInAnyGeocercaAttribute()
    {
        return $this->geocerca_docks !== 'NO' ||
            $this->geocerca_track_trace !== 'NO' ||
            $this->geocerca_cbn !== 'NO' ||
            $this->geocerca_ciudades !== 'NO';
    }

    // Métodos de utilidad
    public function updateWaitingTime()
    {
        if ($this->inicio_espera_descarga) {
            $this->tiempo_espera_minutos = Carbon::now()
                ->diffInMinutes($this->inicio_espera_descarga);
            $this->save();
        }
    }

    public function startWaitingForDischarge()
    {
        if (!$this->inicio_espera_descarga) {
            $this->inicio_espera_descarga = Carbon::now();
            $this->tiempo_espera_minutos = 0;
            $this->save();
        }
    }

    public function getGeocercaStatus()
    {
        return [
            'DOCKS' => $this->geocerca_docks,
            'TRACK AND TRACE' => $this->geocerca_track_trace,
            'CBN' => $this->geocerca_cbn,
            'CIUDADES' => $this->geocerca_ciudades,
        ];
    }
}
