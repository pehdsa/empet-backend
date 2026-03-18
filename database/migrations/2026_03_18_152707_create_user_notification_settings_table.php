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
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('notify_lost_nearby')->default(true);
            $table->boolean('notify_matches')->default(true);
            $table->boolean('notify_sightings')->default(true);
            $table->integer('nearby_radius_km')->default(5);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE user_notification_settings ADD COLUMN location GEOGRAPHY(POINT, 4326)');
        DB::statement('CREATE INDEX user_notification_settings_location_gist ON user_notification_settings USING GIST (location)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
