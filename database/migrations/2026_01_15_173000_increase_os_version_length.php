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
        // Increase os_version to accommodate full user agent strings (can be 150+ chars)
        Schema::table('product_feedback', function (Blueprint $table) {
            $table->string('os_version', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_feedback', function (Blueprint $table) {
            $table->string('os_version', 100)->nullable()->change();
        });
    }
};
