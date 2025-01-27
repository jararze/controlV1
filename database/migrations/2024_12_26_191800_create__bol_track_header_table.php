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
        Schema::create('bol_track_header', function (Blueprint $table) {
            $table->id();
            $table->string('estatus'); // Campo para guardar el estado
            $table->timestamp('hora_actualizacion'); // Hora de actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bol_track_header');
    }
};
