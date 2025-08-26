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
        Schema::create('conduccion_fuera_horario', function (Blueprint $table) {
            $table->id();
            $table->string('NOMBRE', 100)->nullable();
            $table->string('PLACA', 20)->nullable();
            $table->string('TIPO_VEHICULO', 50)->nullable();
            $table->string('MARCA_VEHICULO', 50)->nullable();
            $table->string('MODELO_VEHICULO', 50)->nullable();
            $table->string('VERSION', 20)->nullable();
            $table->string('GRUPO', 100)->nullable();
            $table->string('SUBGRUPO', 100)->nullable();
            $table->string('ID_CONDUCTOR', 20)->nullable();
            $table->string('NOMBRE_CONDUCTOR', 100)->nullable();
            $table->datetime('FECHA_INICIO')->nullable();
            $table->datetime('FECHA_FIN')->nullable();
            $table->decimal('KM_RECORRIDO', 10, 2)->nullable();
            $table->time('HORAS_TRABAJADAS')->nullable();
            $table->string('DIRECCION_INICIO', 200)->nullable();
            $table->string('DIRECCION_FINAL', 200)->nullable();
            $table->string('UBICACION_INICIO', 100)->nullable();
            $table->string('UBICACION_FINAL', 100)->nullable();
            $table->char('batch_id', 36)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->datetime('fecha_registro')->nullable();
            $table->string('final_status', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conduccion_fuera_horario');
    }
};
