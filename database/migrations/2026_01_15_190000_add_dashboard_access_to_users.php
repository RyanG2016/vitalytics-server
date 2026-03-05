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
        Schema::table('users', function (Blueprint $table) {
            // Dashboard access flags for Viewer role
            $table->boolean('has_health_access')->default(true)->after('remember_token');
            $table->boolean('has_analytics_access')->default(true)->after('has_health_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_health_access', 'has_analytics_access']);
        });
    }
};
