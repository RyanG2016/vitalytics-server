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
        Schema::table('device_heartbeats', function (Blueprint $table) {
            $table->timestamp('snoozed_until')->nullable()->after('is_monitoring');
            $table->index('snoozed_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_heartbeats', function (Blueprint $table) {
            $table->dropIndex(['snoozed_until']);
            $table->dropColumn('snoozed_until');
        });
    }
};
