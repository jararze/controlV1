<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $guarded = [];

    public function batchCall()
    {
        return $this->belongsTo(BatchCall::class);
    }
}
