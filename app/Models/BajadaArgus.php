<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BajadaArgus extends Model
{
    protected $connection = 'external_db';
    protected $table = 'bajada_argus';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['estado'];
}
