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
    ];


    public function setLineaAttribute($value)
    {
        if (!empty($value)) {
            // Reemplazar caracteres especiales
            $value = str_replace('Ñ', 'N', $value);
            $value = str_replace('ñ', 'n', $value);
            $value = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $value);
            $value = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $value);

            // Eliminar caracteres no imprimibles
            $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value);
        }

        $this->attributes['linea'] = $value;
    }

    // Similar para otros campos problemáticos
    public function setProductoAttribute($value)
    {
        if (!empty($value)) {
            $value = str_replace('Ñ', 'N', $value);
            $value = str_replace('ñ', 'n', $value);
            $value = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $value);
            $value = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $value);
            $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value);
        }

        $this->attributes['producto'] = $value;
    }

    public function setVariedadAttribute($value)
    {
        if (!empty($value)) {
            $value = str_replace('Ñ', 'N', $value);
            $value = str_replace('ñ', 'n', $value);
            $value = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $value);
            $value = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $value);
            $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value);
        }

        $this->attributes['variedad'] = $value;
    }

    public function setDepositoOrigenAttribute($value)
    {
        if (!empty($value)) {
            $value = str_replace('Ñ', 'N', $value);
            $value = str_replace('ñ', 'n', $value);
            $value = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $value);
            $value = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $value);
            $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value);
        }

        $this->attributes['deposito_origen'] = $value;
    }

    public function setDepositoDestinoAttribute($value)
    {
        if (!empty($value)) {
            $value = str_replace('Ñ', 'N', $value);
            $value = str_replace('ñ', 'n', $value);
            $value = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $value);
            $value = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $value);
            $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value);
        }

        $this->attributes['deposito_destino'] = $value;
    }
}
