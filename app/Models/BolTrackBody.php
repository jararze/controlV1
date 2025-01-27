<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BolTrackBody extends Model
{
    // Define the table name explicitly if it doesn't follow Laravel's naming convention
    protected $table = 'bol_track_body';

    // Fields that are mass-assignable
    protected $fillable = [
        'header_id',  // Foreign key to the header table
        'id_unidad',  // Example field: adjust based on your migration
        'nombre',     // Example field: adjust based on your migration
        'fecha',      // Example field: adjust based on your migration
        'latitud',
        'longitud',
        'motor_encendido',
        'velocidad_kmh',
        'direccion',
        'odometro_dia_m',
        'altura',
        'tiempo_encendido',
        'tiempo_ralenti',
        'tiempo_movimiento',
    ];

    // Define the relationship: one body belongs to a header
    public function header()
    {
        return $this->belongsTo(BolTrackHeader::class, 'bol_track_header_id');
    }
}
