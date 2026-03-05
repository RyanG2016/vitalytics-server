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
        Schema::create('product_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier', 100)->index();
            $table->string('device_id', 100)->nullable()->index();
            $table->string('session_id', 100)->nullable();
            $table->text('message');
            $table->string('category', 50)->default('general')->index(); // general, bug, feature-request, praise
            $table->tinyInteger('rating')->nullable(); // 1-5 star rating
            $table->string('email', 255)->nullable();
            $table->string('user_id', 255)->nullable(); // SDK-provided user identifier
            $table->string('screen', 100)->nullable(); // Screen where feedback was submitted
            $table->string('app_version', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('os_version', 100)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->json('metadata')->nullable(); // Additional context from SDK
            $table->boolean('is_test')->default(false)->index();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Indexes for common queries
            $table->index(['app_identifier', 'created_at']);
            $table->index(['app_identifier', 'category']);
            $table->index(['app_identifier', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_feedback');
    }
};
