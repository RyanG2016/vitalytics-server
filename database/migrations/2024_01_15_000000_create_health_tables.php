<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main events table
        Schema::create('health_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('batch_id')->index();
            $table->string('app_identifier')->index();
            $table->string('environment')->default('production');
            $table->string('device_id')->index();
            $table->string('user_id')->nullable()->index();

            // Event details
            $table->string('level')->index(); // crash, error, warning, info, etc.
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->json('stack_trace')->nullable();

            // Device info
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->string('build_number')->nullable();
            $table->string('platform')->default('iOS');

            // Location (from IP geolocation)
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country', 2)->nullable()->index();

            $table->timestamp('event_timestamp');
            $table->timestamp('received_at');
            $table->timestamps();

            // Indexes for common queries
            $table->index(['app_identifier', 'level', 'event_timestamp']);
            $table->index(['app_identifier', 'event_timestamp']);
        });

        // Aggregated stats per app/time period (for dashboards)
        Schema::create('health_stats', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier');
            $table->string('environment');
            $table->date('date');
            $table->unsignedInteger('hour')->nullable(); // 0-23, null for daily stats

            // Counts by level
            $table->unsignedInteger('crash_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('network_error_count')->default(0);

            // Device stats
            $table->unsignedInteger('unique_devices')->default(0);
            $table->unsignedInteger('heartbeat_count')->default(0);

            $table->timestamps();

            $table->unique(['app_identifier', 'environment', 'date', 'hour']);
        });

        // Alerts configuration
        Schema::create('health_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier');
            $table->string('name');
            $table->string('level'); // crash, error, warning
            $table->string('condition'); // threshold, rate_increase, pattern
            $table->json('config'); // threshold values, time windows, etc.
            $table->string('notify_channel'); // slack, email, sms
            $table->json('notify_config'); // webhook URL, email, phone
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_alerts');
        Schema::dropIfExists('health_stats');
        Schema::dropIfExists('health_events');
    }
};
