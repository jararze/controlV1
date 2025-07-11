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
        'distancia' => 'decimal:2',
        'puntaje' => 'decimal:2',
        'tarifa' => 'decimal:2',
        'salida' => 'decimal:2',
        'entrada' => 'decimal:2',
        'valor_producto' => 'decimal:2',
    ];
    // Relationship with history
    public function histories()
    {
        return $this->hasMany(TruckHistory::class, 'planilla', 'planilla')
            ->where('patente', $this->patente)
            ->where('cod_producto', $this->cod_producto);
    }

    // Scope for efficient bulk lookups by composite key
    public function scopeByCompositeKey($query, $planilla, $patente, $codProducto = null)
    {
        return $query->where('planilla', $planilla)
            ->where('patente', $patente)
            ->where('cod_producto', $codProducto);
    }

    // Helper method to get composite key for efficient lookups
    public function getCompositeKey()
    {
        return $this->planilla . '|' . $this->patente . '|' . ($this->cod_producto ?? '');
    }

    // Optimized bulk insert method that disables events for performance
    public static function bulkInsert(array $data)
    {
        // Add timestamps to all records if not present
        $now = now();
        foreach ($data as &$item) {
            if (!isset($item['created_at'])) {
                $item['created_at'] = $now;
            }
            if (!isset($item['updated_at'])) {
                $item['updated_at'] = $now;
            }
        }

        return static::insert($data);
    }

    // Optimized bulk update method
    public function updateFromArray(array $data)
    {
        $data['updated_at'] = now();
        return $this->update($data);
    }
}
