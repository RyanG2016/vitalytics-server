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
        Schema::create('event_label_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('app_identifier', 100);
            $table->enum('mapping_type', ['screen', 'element', 'feature', 'form', 'event_type']);
            $table->string('raw_value', 255);
            $table->string('friendly_label', 255);
            $table->string('client_suggested_label', 255)->nullable();
            $table->timestamps();

            // Unique constraint: one mapping per raw value per type per app
            $table->unique(['app_identifier', 'mapping_type', 'raw_value'], 'unique_mapping');

            // Index for quick lookups
            $table->index(['app_identifier', 'mapping_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_label_mappings');
    }
};
