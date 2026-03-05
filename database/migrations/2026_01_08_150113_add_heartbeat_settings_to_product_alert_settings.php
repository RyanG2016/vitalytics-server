<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_alert_settings', function (Blueprint $table) {
            $table->boolean('heartbeat_enabled')->default(false)->after('alert_on_test_data');
            $table->integer('heartbeat_timeout_minutes')->default(15)->after('heartbeat_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('product_alert_settings', function (Blueprint $table) {
            $table->dropColumn(['heartbeat_enabled', 'heartbeat_timeout_minutes']);
        });
    }
};
