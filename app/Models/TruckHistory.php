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

    // Tu método existente
    public function getOriginalValue($key)
    {
        return data_get($this->original_data, $key);
    }

    // Relationship back to truck (using composite key)
    public function truck()
    {
        return $this->belongsTo(Truck::class, 'planilla', 'planilla')
            ->where('patente', $this->patente)
            ->where('cod_producto', $this->cod_producto);
    }

    // Scope for getting history by composite key
    public function scopeByCompositeKey($query, $planilla, $patente, $codProducto = null)
    {
        return $query->where('planilla', $planilla)
            ->where('patente', $patente)
            ->where('cod_producto', $codProducto);
    }

    // Optimized bulk insert for history records
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

            // Ensure original_data is JSON string if it's an array
            if (isset($item['original_data']) && is_array($item['original_data'])) {
                $item['original_data'] = json_encode($item['original_data']);
            }
        }

        return static::insert($data);
    }

    // Helper method to create history record from truck model
    public static function createFromTruck(Truck $truck, $changeType = 'UPDATE')
    {
        return static::create([
            'planilla' => $truck->planilla,
            'patente' => $truck->patente,
            'cod_producto' => $truck->cod_producto,
            'fecha_salida' => $truck->fecha_salida,
            'batch_id' => $truck->batch_id,
            'original_data' => $truck->toArray(),
            'change_type' => $changeType,
            'changed_at' => now(),
        ]);
    }
}
