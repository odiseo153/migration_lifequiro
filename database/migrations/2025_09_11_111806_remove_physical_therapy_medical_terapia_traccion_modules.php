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
        Schema::table('medical_terapia_tracion_modules', function (Blueprint $table) {
            $table->dropForeign(['physical_therapy']);
            $table->dropColumn('physical_therapy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_terapia_tracion_modules', function (Blueprint $table) {
            $table->foreignId('physical_therapy')->constrained('physical_therapy_categories');
        });
    }
};
