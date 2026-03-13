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
        Schema::create('pet_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('address_hint')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamp('found_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pet_reports ADD COLUMN location GEOGRAPHY(POINT, 4326)');
        DB::statement('CREATE INDEX pet_reports_location_gist ON pet_reports USING GIST (location)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_reports');
    }
};
