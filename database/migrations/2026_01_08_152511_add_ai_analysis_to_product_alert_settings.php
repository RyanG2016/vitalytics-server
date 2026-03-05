<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_alert_settings', function (Blueprint $table) {
            $table->boolean('ai_analysis_enabled')->default(false)->after('heartbeat_timeout_minutes');
            $table->integer('ai_analysis_hour')->default(6)->after('ai_analysis_enabled'); // 6 AM default
        });
    }

    public function down(): void
    {
        Schema::table('product_alert_settings', function (Blueprint $table) {
            $table->dropColumn(['ai_analysis_enabled', 'ai_analysis_hour']);
        });
    }
};
