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
        Schema::table('health_events', function (Blueprint $table) {
            $table->timestamp('dismissed_at')->nullable()->after('is_test');
            $table->unsignedBigInteger('dismissed_by')->nullable()->after('dismissed_at');
            $table->text('dismissed_note')->nullable()->after('dismissed_by');
            
            $table->index('dismissed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_events', function (Blueprint $table) {
            $table->dropIndex(['dismissed_at']);
            $table->dropColumn(['dismissed_at', 'dismissed_by', 'dismissed_note']);
        });
    }
};
