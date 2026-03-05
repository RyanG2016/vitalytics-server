<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main analytics events table
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 36)->unique();
            $table->string('batch_id', 36)->index();
            $table->string('app_identifier', 100)->index();
            $table->string('session_id', 36)->index();
            $table->string('anonymous_user_id', 100)->nullable()->index();

            // Event data
            $table->string('event_name', 100)->index();
            $table->string('event_category', 50)->index();
            $table->string('screen_name', 200)->nullable();
            $table->string('element_id', 100)->nullable();
            $table->json('properties')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('referrer', 200)->nullable();

            // Device info
            $table->string('device_id', 36)->index();
            $table->string('device_model', 100)->nullable();
            $table->string('platform', 20);
            $table->string('os_version', 50)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('screen_resolution', 20)->nullable();
            $table->string('language', 10)->nullable();

            // Location (from IP)
            $table->string('country', 2)->nullable();
            $table->string('region', 100)->nullable();

            // Test flag (like health events)
            $table->boolean('is_test')->default(false)->index();

            // Timestamps
            $table->timestamp('event_timestamp');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['app_identifier', 'event_timestamp']);
            $table->index(['app_identifier', 'event_name', 'event_timestamp']);
            $table->index(['app_identifier', 'is_test', 'event_timestamp']);
        });

        // Sessions table for tracking user sessions
        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 36)->unique();
            $table->string('app_identifier', 100)->index();
            $table->string('device_id', 36)->index();
            $table->string('anonymous_user_id', 100)->nullable();

            // Session metrics
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('screens_viewed')->default(0);

            // Device info snapshot
            $table->string('platform', 20);
            $table->string('app_version', 50)->nullable();
            $table->string('country', 2)->nullable();

            // Test flag
            $table->boolean('is_test')->default(false);

            $table->timestamps();

            $table->index(['app_identifier', 'started_at']);
            $table->index(['app_identifier', 'is_test', 'started_at']);
        });

        // Daily aggregated stats for fast dashboard queries
        Schema::create('analytics_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('app_identifier', 100);
            $table->string('event_name', 100);
            $table->string('event_category', 50);

            // Counts
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('unique_sessions')->default(0);
            $table->unsignedInteger('unique_devices')->default(0);

            // Test flag (separate stats for test vs production)
            $table->boolean('is_test')->default(false);

            $table->timestamps();

            $table->unique(['date', 'app_identifier', 'event_name', 'is_test'], 'analytics_daily_unique');
            $table->index(['app_identifier', 'date']);
            $table->index(['app_identifier', 'is_test', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_stats');
        Schema::dropIfExists('analytics_sessions');
        Schema::dropIfExists('analytics_events');
    }
};
