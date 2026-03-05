<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('display_order')->default(0)->after('is_active');
            $table->index('display_order');
        });

        // Set initial order based on current ordering (by name)
        $products = DB::table('products')->orderBy('name')->get();
        foreach ($products as $index => $product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update(['display_order' => $index + 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['display_order']);
            $table->dropColumn('display_order');
        });
    }
};
