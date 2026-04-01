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
        Schema::create('pet_sighting_characteristics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_sighting_id')->constrained('pet_sightings')->cascadeOnDelete();
            $table->foreignId('characteristic_id')->constrained('characteristics')->cascadeOnDelete();

            $table->unique(['pet_sighting_id', 'characteristic_id'], 'pet_sighting_char_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_sighting_characteristics');
    }
};
