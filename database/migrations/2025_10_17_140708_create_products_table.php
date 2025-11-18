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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('in_stock')->default(true);
            $table->enum('type', ['simple', 'variable', 'bundled'])->default('simple');
            $table->boolean('should_feature_on_home_page')->default(0);
            $table->boolean('is_new_product')->default(0);
            $table->boolean('is_best_seller')->default(0);
            $table->json('tags')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->double('single_product_price')->nullable();
            $table->boolean('in_draft')->default(1);
            $table->boolean('track_inventory_for_all_variant')->default(false);
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('enable_auto_reorder_alerts')->default(false);

            $table->tinyInteger('bundled_product_price_type')->default(0)->comment('0 = Sum of all products price | 1 = Fixed bundle price');
            $table->double('bundled_product_fixed_price')->default(0);

            $table->boolean('bundled_product_discount_type')->default(0)->comment('0 = Percentage | 1 = Fixed');
            $table->double('bundled_product_discount')->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
