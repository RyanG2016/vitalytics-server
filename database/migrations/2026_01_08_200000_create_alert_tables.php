<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product-level alert settings
        Schema::create('product_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // Teams configuration
            $table->text('teams_webhook_url')->nullable();
            $table->boolean('teams_enabled')->default(false);
            
            // Email configuration
            $table->boolean('email_enabled')->default(true);
            
            // Critical alert settings (crash)
            $table->integer('critical_cooldown_minutes')->default(30); // Same error suppressed for 30 min
            $table->integer('critical_reminder_hours')->default(1);    // Re-alert every hour if not cleared
            
            // Non-critical alert settings (error, warning, network)
            $table->integer('noncritical_threshold')->default(5);       // Alert after X occurrences
            $table->integer('noncritical_window_minutes')->default(60); // Within Y minutes
            $table->integer('noncritical_cooldown_hours')->default(4);  // Then suppress for Z hours
            
            // Business hours (future feature)
            $table->boolean('business_hours_only')->default(false);
            $table->time('business_hours_start')->nullable();
            $table->time('business_hours_end')->nullable();
            $table->string('timezone')->default('America/Winnipeg');
            
            $table->timestamps();
            
            $table->unique('product_id');
        });

        // Alert subscribers (users and external emails)
        Schema::create('alert_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email')->nullable(); // For external subscribers
            $table->string('name')->nullable();  // Display name for external subscribers
            
            // What they receive
            $table->boolean('receive_critical')->default(true);
            $table->boolean('receive_noncritical')->default(false);
            
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            
            // Either user_id or email must be set
            $table->index(['product_id', 'user_id']);
            $table->index(['product_id', 'email']);
        });

        // Track sent alerts for throttling
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('app_identifier')->nullable();
            $table->string('level'); // crash, error, warning, networkError
            $table->string('error_hash'); // MD5 of error message for grouping
            $table->string('channel'); // teams, email
            $table->integer('occurrence_count')->default(1);
            $table->timestamp('first_occurrence_at');
            $table->timestamp('last_occurrence_at');
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'error_hash', 'level']);
            $table->index(['app_identifier', 'error_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('alert_subscribers');
        Schema::dropIfExists('product_alert_settings');
    }
};
