<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bol_track_body', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bol_track_header_id');
            $table->string('id_unidad'); // ID de la unidad
            $table->string('nombre'); // Nombre de la unidad
            $table->timestamp('fecha'); // Fecha del registro
            $table->decimal('latitud', 10, 7); // Latitud
            $table->decimal('longitud', 10, 7); // Longitud
            $table->boolean('motor_encendido'); // Motor encendido (0/1)
            $table->integer('velocidad_kmh'); // Velocidad en km/h
            $table->integer('direccion'); // Dirección
            $table->integer('odometro_dia_m')->nullable(); // Odómetro del día (en metros)
            $table->integer('altura')->nullable(); // Altura
            $table->integer('tiempo_encendido')->nullable(); // Tiempo encendido
            $table->integer('tiempo_ralenti')->nullable(); // Tiempo en ralenti
            $table->string('tiempo_movimiento')->nullable(); // Tiempo de movimiento
            $table->string('tiempoMovimientoFormated')->nullable(); // Tiempo de movimiento
            $table->timestamps();

            $table->foreign('bol_track_header_id')->references('id')->on('bol_track_header')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bol_track_body');
    }
};
