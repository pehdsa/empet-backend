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
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('code_hash')->nullable();
            $table->timestamp('code_expires_at')->nullable();
            $table->string('reset_token_hash')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
