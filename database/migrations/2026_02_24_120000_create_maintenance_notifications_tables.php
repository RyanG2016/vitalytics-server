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
        // Main notifications table
        Schema::create('maintenance_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('message');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->boolean('dismissible')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Index for active query performance
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        // Pivot table for products (many-to-many)
        Schema::create('maintenance_notification_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_notification_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            $table->foreign('maintenance_notification_id', 'maint_notif_fk')
                ->references('id')->on('maintenance_notifications')
                ->cascadeOnDelete();
            $table->foreign('product_id', 'maint_prod_fk')
                ->references('id')->on('products')
                ->cascadeOnDelete();

            $table->unique(['maintenance_notification_id', 'product_id'], 'mnp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_notification_product');
        Schema::dropIfExists('maintenance_notifications');
    }
};
