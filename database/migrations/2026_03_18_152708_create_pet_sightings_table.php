<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pet_sightings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained('pet_reports')->cascadeOnDelete();
            $table->string('address_hint', 500)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('sighted_at');
            $table->boolean('share_phone')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_id', 'created_at']);
        });

        DB::statement('ALTER TABLE pet_sightings ADD COLUMN location GEOGRAPHY(POINT, 4326) NOT NULL');
        DB::statement('CREATE INDEX pet_sightings_location_gist ON pet_sightings USING GIST (location)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_sightings');
    }
};
