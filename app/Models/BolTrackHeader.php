<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BolTrackHeader extends Model
{
    // Define the table name explicitly if it doesn't follow Laravel's naming convention
    protected $table = 'bol_track_header';

    // Fields that are mass-assignable
    protected $fillable = [
        'estatus',  // Example field: adjust based on your migration
        'hora_actualizacion', // Example field: adjust based on your migration
    ];

    // Define the relationship: one header has many bodies
    public function bodies()
    {
        return $this->hasMany(BolTrackBody::class, 'bol_track_header_id');
    }
}
