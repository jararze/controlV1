<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Geocerca extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_grupo',
        'nombre_grupo',
        'id_geocerca',
        'codigo',
        'nombre_geocerca',
        'puntos',
        'puntos_raw',
        'activa'
    ];

    protected $casts = [
        'puntos' => 'array',
        'activa' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('activa', true);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('nombre_grupo', $group);
    }

    public function scopeByIdGroup($query, $idGroup)
    {
        return $query->where('id_grupo', $idGroup);
    }

    // Método para verificar si un punto está dentro de la geocerca
    public function containsPoint($latitude, $longitude)
    {
        if (empty($this->puntos) || count($this->puntos) < 3) {
            return false;
        }

        return $this->pointInPolygon($latitude, $longitude, $this->puntos);
    }

    private function pointInPolygon($latitude, $longitude, $polygon)
    {
        $vertices = count($polygon);
        $intersections = 0;

        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $polygon[$i][1]; // lng
            $yi = $polygon[$i][0]; // lat
            $xj = $polygon[$j][1]; // lng
            $yj = $polygon[$j][0]; // lat

            if ((($yi > $latitude) !== ($yj > $latitude)) &&
                ($longitude < ($xj - $xi) * ($latitude - $yi) / ($yj - $yi) + $xi)) {
                $intersections++;
            }
        }

        return ($intersections % 2) === 1;
    }

    // Método estático para obtener geocercas agrupadas (equivalente al cache Python)
    public static function getByGroups()
    {
        return self::active()
            ->orderBy('nombre_grupo')
            ->orderBy('nombre_geocerca')
            ->get()
            ->groupBy('nombre_grupo');
    }
}
