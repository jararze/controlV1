<?php

namespace App\Models\Uploads;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Model;

class Matrix extends Model
{
    protected $guarded = "";

    protected $table = 'upload_matrix';

    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    public function driver()
    {
        return $this->hasOne(Driver::class, 'placa', 'patente');
    }
}
