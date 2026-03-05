<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes device_id nullable to support anonymous analytics mode.
     * Events without device_id are considered "anonymous" and will be
     * tracked separately from "identified" events in the dashboard.
     */
    public function up(): void
    {
        // Make device_id nullable in analytics_events
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('device_id', 100)->nullable()->change();
        });

        // Make device_id nullable in analytics_sessions
        Schema::table('analytics_sessions', function (Blueprint $table) {
            $table->string('device_id', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert device_id to required in analytics_events
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('device_id', 100)->nullable(false)->change();
        });

        // Revert device_id to required in analytics_sessions
        Schema::table('analytics_sessions', function (Blueprint $table) {
            $table->string('device_id', 100)->nullable(false)->change();
        });
    }
};
