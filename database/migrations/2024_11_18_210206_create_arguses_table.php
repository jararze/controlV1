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
        Schema::create('arguses', function (Blueprint $table) {
            $table->id();
            $table->string('operacion')->nullable();
            $table->string('patente', 50)->nullable();
            $table->date('dia')->nullable();
            $table->string('evento', 100)->nullable();
            $table->string('motorista')->nullable();
            $table->dateTime('hora_alarma')->nullable();
            $table->integer('velocidade')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('event_id')->nullable();
            $table->uuid('batch_id')->nullable()->nullable();                     // Batch ID
            $table->string('file_name', 255)->nullable();
            $table->dateTime('fecha_registro')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('final_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arguses');
    }
};
