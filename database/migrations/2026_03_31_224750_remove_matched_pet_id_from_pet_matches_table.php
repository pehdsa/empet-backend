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
        // Remove old CHECK constraint
        DB::statement('ALTER TABLE pet_matches DROP CONSTRAINT IF EXISTS pet_matches_one_source_check');

        // Delete old pet-based matches (they used matched_pet_id)
        DB::table('pet_matches')->whereNotNull('matched_pet_id')->delete();

        Schema::table('pet_matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matched_pet_id');
            $table->foreignId('sighting_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pet_matches', function (Blueprint $table) {
            $table->foreignId('matched_pet_id')->nullable()->after('report_id')
                ->constrained('pets')->cascadeOnDelete();
            $table->foreignId('sighting_id')->nullable()->change();
        });

        DB::statement('
            ALTER TABLE pet_matches ADD CONSTRAINT pet_matches_one_source_check CHECK (
                (matched_pet_id IS NOT NULL AND sighting_id IS NULL)
                OR (sighting_id IS NOT NULL AND matched_pet_id IS NULL)
            )
        ');
    }
};
