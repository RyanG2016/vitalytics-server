<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_alert_settings', function (Blueprint $table) {
            $table->boolean('push_enabled')->default(true)->after('email_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('product_alert_settings', function (Blueprint $table) {
            $table->dropColumn('push_enabled');
        });
    }
};
