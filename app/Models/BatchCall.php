<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchCall extends Model
{
    protected $guarded = [];

    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }
}
