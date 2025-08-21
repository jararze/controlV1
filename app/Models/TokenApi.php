<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class TokenApi extends Model
{
    use HasFactory;

    protected $table = 'token_apis';

    protected $guarded = [];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_expiracion' => 'datetime'
    ];

    public function getTiempoRestanteAttribute()
    {
        if (!$this->fecha_expiracion) {
            return 'Desconocido';
        }

        $ahora = Carbon::now();
        if ($this->fecha_expiracion < $ahora) {
            return 'Expirado';
        }

        return $this->fecha_expiracion->diffForHumans($ahora);
    }

    public function estaExpirado()
    {
        if (!$this->fecha_expiracion) {
            return false;
        }

        return $this->fecha_expiracion < Carbon::now();
    }

    public static function tokenActivo()
    {
        return self::where('activo', true)
            ->where(function($query) {
                $query->whereNull('fecha_expiracion')
                    ->orWhere('fecha_expiracion', '>', Carbon::now());
            })
            ->latest()
            ->first();
    }
}
