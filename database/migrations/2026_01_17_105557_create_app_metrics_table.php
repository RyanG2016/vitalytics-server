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
        // Raw metrics table - stores individual metric events
        Schema::create('app_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('metric_id')->unique();
            $table->string('app_identifier', 100)->index();
            $table->string('device_id', 100)->nullable()->index();
            $table->string('name', 100)->index(); // e.g., 'ai_tokens', 'api_calls'
            $table->json('data'); // The metric data (tokens, duration, etc.)
            $table->string('aggregate_type', 20)->default('sum'); // sum, avg, min, max, count
            $table->json('tags')->nullable(); // Tags for filtering/grouping
            $table->string('user_id', 100)->nullable()->index();
            $table->boolean('is_test')->default(false)->index();
            $table->timestamp('metric_timestamp');
            $table->timestamp('received_at');
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['app_identifier', 'name', 'metric_timestamp']);
            $table->index(['app_identifier', 'name', 'is_test']);
        });

        // Aggregated metrics table - stores hourly/daily rollups
        Schema::create('app_metrics_aggregated', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier', 100)->index();
            $table->string('name', 100)->index();
            $table->string('period_type', 10); // 'hourly', 'daily'
            $table->timestamp('period_start')->index();
            $table->timestamp('period_end');

            // Aggregated values
            $table->bigInteger('count')->default(0);
            $table->decimal('sum_value', 20, 4)->default(0);
            $table->decimal('avg_value', 20, 4)->default(0);
            $table->decimal('min_value', 20, 4)->nullable();
            $table->decimal('max_value', 20, 4)->nullable();

            // For AI tokens specifically
            $table->bigInteger('total_input_tokens')->default(0);
            $table->bigInteger('total_output_tokens')->default(0);
            $table->bigInteger('total_tokens')->default(0);
            $table->decimal('total_cost_cents', 12, 2)->default(0);

            // Breakdown by provider/model (JSON for flexibility)
            $table->json('breakdown')->nullable();

            $table->boolean('is_test')->default(false)->index();
            $table->timestamps();

            // Unique constraint for rollups
            $table->unique(['app_identifier', 'name', 'period_type', 'period_start', 'is_test'], 'metrics_agg_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_metrics_aggregated');
        Schema::dropIfExists('app_metrics');
    }
};
