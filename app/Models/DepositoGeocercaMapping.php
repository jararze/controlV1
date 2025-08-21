<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DepositoGeocercaMapping extends Model
{
    use HasFactory;

    protected $table = 'deposito_geocerca_mappings';

    protected $fillable = [
        'deposito_destino',
        'ciudad_geocerca',
        'cbn_geocerca',
        'track_trace_geocerca',
        'docks_geocerca',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    public function scopeForDeposito($query, $deposito)
    {
        return $query->where('deposito_destino', $deposito);
    }

    // Métodos de utilidad
    public function getGeocercaMapping()
    {
        return [
            'CIUDADES' => $this->ciudad_geocerca,
            'CBN' => $this->cbn_geocerca,
            'TRACK AND TRACE' => $this->track_trace_geocerca,
            'DOCKS' => $this->docks_geocerca,
        ];
    }

    public static function getMappingForDeposito($deposito)
    {
        $mapping = self::active()->forDeposito($deposito)->first();

        return $mapping ? $mapping->getGeocercaMapping() : null;
    }
}
