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
        Schema::create('app_secrets', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier')->index();
            $table->string('secret'); // Hashed secret
            $table->string('label')->nullable(); // e.g., "Primary", "Rotating out"
            $table->timestamp('expires_at')->nullable(); // Null = never expires
            $table->timestamps();

            // Index for quick lookups
            $table->index(['app_identifier', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_secrets');
    }
};
