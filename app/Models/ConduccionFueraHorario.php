<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConduccionFueraHorario extends Model
{
    use HasFactory;

    protected $table = 'conduccion_fuera_horario';

    protected $fillable = [
        'NOMBRE',
        'PLACA',
        'TIPO_VEHICULO',
        'MARCA_VEHICULO',
        'MODELO_VEHICULO',
        'VERSION',
        'GRUPO',
        'SUBGRUPO',
        'ID_CONDUCTOR',
        'NOMBRE_CONDUCTOR',
        'FECHA_INICIO',
        'FECHA_FIN',
        'KM_RECORRIDO',
        'HORAS_TRABAJADAS',
        'DIRECCION_INICIO',
        'DIRECCION_FINAL',
        'UBICACION_INICIO',
        'UBICACION_FINAL',
        'batch_id',
        'file_name',
        'fecha_registro',
        'final_status'
    ];

    protected $dates = [
        'FECHA_INICIO',
        'FECHA_FIN',
        'fecha_registro'
    ];

    protected $casts = [
        'KM_RECORRIDO' => 'decimal:2',
        'FECHA_INICIO' => 'datetime',
        'FECHA_FIN' => 'datetime',
        'fecha_registro' => 'datetime',
        'HORAS_TRABAJADAS' => 'string'
    ];
}
