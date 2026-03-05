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
        // Add geo columns to analytics_events
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('region');
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            // Add index for geo queries
            $table->index(['latitude', 'longitude'], 'analytics_events_geo_index');
        });

        // Add geo columns to analytics_sessions
        Schema::table('analytics_sessions', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('country');
            $table->string('region', 100)->nullable()->after('city');
            $table->decimal('latitude', 10, 7)->nullable()->after('region');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            // Add index for geo queries
            $table->index(['latitude', 'longitude'], 'analytics_sessions_geo_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex('analytics_events_geo_index');
            $table->dropColumn(['city', 'latitude', 'longitude']);
        });

        Schema::table('analytics_sessions', function (Blueprint $table) {
            $table->dropIndex('analytics_sessions_geo_index');
            $table->dropColumn(['city', 'region', 'latitude', 'longitude']);
        });
    }
};
