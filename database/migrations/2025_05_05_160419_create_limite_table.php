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
        Schema::create('limite', function (Blueprint $table) {
            $table->id();
            $table->string('PLACA', 20)->nullable();
            $table->string('GRUPO', 100)->nullable();
            $table->text('DESCRIPCION')->nullable();
            $table->dateTime('FECHA_ALERTA')->nullable();
            $table->time('TIEMPO_MOVIMIENTO')->nullable();
            $table->string('UBICACION', 200)->nullable();
            $table->string('DIRECCION', 200)->nullable();
            $table->time('TIEMPO_ENCENDIDO')->nullable();
            $table->time('TIEMPO_RALENTI')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->string('file_name')->nullable();
            $table->dateTime('fecha_registro')->nullable();
            $table->string('final_status', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('limite');
    }
};
