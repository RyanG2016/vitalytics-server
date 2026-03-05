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
        Schema::create('product_icons', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->unique(); // e.g., 'myapp', 'another-app'
            $table->string('icon_path')->nullable(); // Path to uploaded icon image
            $table->string('icon_url')->nullable(); // External URL for icon
            $table->string('color')->nullable(); // Optional color override
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_icons');
    }
};
