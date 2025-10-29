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
