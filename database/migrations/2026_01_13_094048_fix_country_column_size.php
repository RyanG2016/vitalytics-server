<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix country column size - was varchar(2) for codes, but ip-api returns full names
     */
    public function up(): void
    {
        // Fix analytics_events country column
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->change();
        });

        // Fix analytics_sessions country column
        Schema::table('analytics_sessions', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Can't safely reverse this as data may exceed varchar(2)
    }
};
