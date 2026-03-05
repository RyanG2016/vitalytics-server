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
        Schema::create('app_configs', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier');
            $table->string('config_key', 100);
            $table->string('filename');
            $table->text('description')->nullable();
            $table->string('content_type', 50)->default('ini');
            $table->boolean('embed_version_header')->default(true);
            $table->text('version_header_template')->nullable();
            $table->unsignedInteger('current_version')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['app_identifier', 'config_key']);
            $table->index('app_identifier');
        });

        Schema::create('app_config_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_config_id')->constrained('app_configs')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('content');
            $table->string('content_hash', 64);
            $table->unsignedInteger('content_size');
            $table->text('change_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['app_config_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_config_versions');
        Schema::dropIfExists('app_configs');
    }
};
