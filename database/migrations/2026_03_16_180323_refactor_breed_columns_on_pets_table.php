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
        Schema::table('pets', function (Blueprint $table) {
            $table->foreignId('breed_id')->nullable()->after('sex')->constrained('breeds')->nullOnDelete();
            $table->foreignId('secondary_breed_id')->nullable()->after('breed_id')->constrained('breeds')->nullOnDelete();
            $table->string('breed_description', 255)->nullable()->after('secondary_breed_id');

            $table->dropColumn(['breed', 'secondary_breed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('breed_id');
            $table->dropConstrainedForeignId('secondary_breed_id');
            $table->dropColumn('breed_description');

            $table->string('breed')->nullable()->after('sex');
            $table->string('secondary_breed')->nullable()->after('breed');
        });
    }
};
