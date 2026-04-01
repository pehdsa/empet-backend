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
        Schema::create('pet_sighting_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_sighting_id')->constrained('pet_sightings')->cascadeOnDelete();
            $table->string('path', 500);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_sighting_photos');
    }
};
