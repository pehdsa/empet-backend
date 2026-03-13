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
        Schema::create('pet_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('pet_reports')->cascadeOnDelete();
            $table->foreignId('matched_pet_id')->constrained('pets')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_matches');
    }
};
