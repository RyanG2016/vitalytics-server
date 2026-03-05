<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('app_identifier');
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->timestamp('last_heartbeat_at');
            $table->timestamp('last_alert_at')->nullable();
            $table->boolean('is_monitoring')->default(true);
            $table->timestamps();

            // Unique constraint per device per app
            $table->unique(['app_identifier', 'device_id']);
            $table->index(['product_id', 'is_monitoring']);
            $table->index('last_heartbeat_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_heartbeats');
    }
};
