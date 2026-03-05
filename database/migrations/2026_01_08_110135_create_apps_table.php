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
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('identifier')->unique();
            $table->string('name');
            $table->enum('platform', ['ios', 'android', 'chrome', 'windows', 'macos', 'web']);
            $table->string('icon')->default('fa-cube');
            $table->string('color', 20)->default('blue');
            $table->text('api_key_encrypted')->nullable();
            $table->string('api_key_prefix', 20)->nullable();
            $table->timestamp('api_key_generated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
            $table->index('api_key_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
