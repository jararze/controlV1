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
        Schema::create('geocercas', function (Blueprint $table) {
            $table->id();

            // Campos del Excel original
            $table->integer('id_grupo')->index();
            $table->string('nombre_grupo')->index(); // CBN, DOCKS, etc.
            $table->integer('id_geocerca')->index();
            $table->string('codigo')->nullable();
            $table->string('nombre_geocerca')->index(); // PLANTA TAQUINA, CD COBIJA, etc.

            // Puntos procesados
            $table->json('puntos')->comment('Array de coordenadas [lat, lng]');
            $table->text('puntos_raw')->comment('String original del Excel');

            // Campos de control
            $table->boolean('activa')->default(true)->index();
            $table->timestamps();

            // Índices
            $table->index(['nombre_grupo', 'activa']);
            $table->index(['id_grupo', 'id_geocerca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geocercas');
    }
};
