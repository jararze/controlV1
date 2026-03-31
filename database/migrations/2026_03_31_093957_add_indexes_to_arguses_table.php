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
        Schema::table('arguses', function (Blueprint $table) {
            $table->index('batch_id');
            $table->index('patente');
        });
    }

    public function down(): void
    {
        Schema::table('arguses', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropIndex(['patente']);
        });
    }
};
