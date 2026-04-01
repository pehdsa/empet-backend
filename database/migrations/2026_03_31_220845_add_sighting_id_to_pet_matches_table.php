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
        Schema::table('pet_matches', function (Blueprint $table) {
            $table->foreignId('sighting_id')->nullable()->after('matched_pet_id')
                ->constrained('pet_sightings');

            // Make matched_pet_id nullable for transition period
            $table->foreignId('matched_pet_id')->nullable()->change();
        });

        // Unique constraint for sighting-based matches
        Schema::table('pet_matches', function (Blueprint $table) {
            $table->unique(['report_id', 'sighting_id'], 'pet_matches_report_sighting_unique');
        });

        // CHECK: exactly one of matched_pet_id or sighting_id must be set
        DB::statement('
            ALTER TABLE pet_matches ADD CONSTRAINT pet_matches_one_source_check CHECK (
                (matched_pet_id IS NOT NULL AND sighting_id IS NULL)
                OR (sighting_id IS NOT NULL AND matched_pet_id IS NULL)
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE pet_matches DROP CONSTRAINT IF EXISTS pet_matches_one_source_check');

        Schema::table('pet_matches', function (Blueprint $table) {
            $table->dropUnique('pet_matches_report_sighting_unique');
            $table->dropConstrainedForeignId('sighting_id');
            $table->foreignId('matched_pet_id')->nullable(false)->change();
        });
    }
};
