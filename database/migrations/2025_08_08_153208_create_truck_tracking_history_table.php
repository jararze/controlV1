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
        Schema::create('truck_tracking_history', function (Blueprint $table) {
            $table->id();
            $table->string('patente')->index();
            $table->string('planilla')->nullable()->index();

            // Ubicación
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('velocidad_kmh', 5, 2)->nullable();
            $table->integer('direccion')->nullable();

            // Estados de geocercas
            $table->string('geocerca_docks')->default('NO');
            $table->string('geocerca_track_trace')->default('NO');
            $table->string('geocerca_cbn')->default('NO');
            $table->string('geocerca_ciudades')->default('NO');

            // Progreso
            $table->decimal('porcentaje_entrega', 5, 2)->default(0.00)->index();
            $table->string('estado_entrega', 50)->default('EN_TRANSITO');

            // Tiempo de espera
            $table->integer('tiempo_espera_minutos')->default(0)->index();
            $table->string('estado_descarga', 50)->default('NO_INICIADO')->index();

            $table->string('api_timestamp')->nullable();
            $table->timestamps();

            // Índices
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('truck_tracking_history');
    }
};
