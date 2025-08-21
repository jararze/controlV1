<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('token_apis', function (Blueprint $table) {
            $table->id();
            $table->text('token');
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_expiracion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'fecha_expiracion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_apis');
    }
};
