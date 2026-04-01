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
        Schema::create('pet_sightings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('address_hint', 500)->nullable();
            $table->timestamp('sighted_at');
            $table->string('species');
            $table->string('size')->nullable();
            $table->string('sex')->nullable();
            $table->string('color', 100)->nullable();
            $table->foreignId('breed_id')->nullable()->constrained('breeds')->nullOnDelete();
            $table->boolean('share_phone')->default(false);
            $table->geography('location', subtype: 'point', srid: 4326);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['species', 'created_at']);
            $table->spatialIndex('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_sightings');
    }
};
