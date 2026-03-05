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
        Schema::table('product_alert_settings', function (Blueprint $table) {
            $table->boolean('exclude_weekends')->default(false)->after('business_hours_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_alert_settings', function (Blueprint $table) {
            $table->dropColumn('exclude_weekends');
        });
    }
};
