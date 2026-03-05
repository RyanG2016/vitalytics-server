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
        // Registration tokens - short-lived tokens for device provisioning
        Schema::create('registration_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier');
            $table->string('token_prefix', 12);
            $table->string('token_hash', 64);
            $table->string('name')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('expires_at');
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();

            $table->index('token_prefix');
            $table->index(['app_identifier', 'is_revoked']);
        });

        // Device API keys - permanent keys issued to registered devices
        Schema::create('device_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier');
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->string('device_hostname')->nullable();
            $table->string('device_os', 100)->nullable();
            $table->string('key_prefix', 12);
            $table->string('key_hash', 64);
            $table->foreignId('registration_token_id')->nullable()->constrained('registration_tokens')->nullOnDelete();
            $table->string('registered_ip', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason')->nullable();
            $table->timestamps();

            $table->unique(['app_identifier', 'device_id']);
            $table->index('key_prefix');
            $table->index(['app_identifier', 'is_revoked']);
        });

        // Audit logs for device registration events
        Schema::create('device_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->string('app_identifier')->nullable();
            $table->string('device_id')->nullable();
            $table->foreignId('registration_token_id')->nullable()->constrained('registration_tokens')->nullOnDelete();
            $table->foreignId('device_api_key_id')->nullable()->constrained('device_api_keys')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at');

            $table->index(['event_type', 'created_at']);
            $table->index(['app_identifier', 'created_at']);
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_audit_logs');
        Schema::dropIfExists('device_api_keys');
        Schema::dropIfExists('registration_tokens');
    }
};
