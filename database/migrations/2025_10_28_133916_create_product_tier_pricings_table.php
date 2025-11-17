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
        Schema::create('product_tier_pricings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('unit_type')->default(false)->comment('0 = Base Unit | 1 = Additional Unit');
            $table->unsignedBigInteger('product_additional_unit_id');
            $table->double('min_qty')->default(0);
            $table->double('max_qty')->default(0);
            $table->double('price_per_unit')->default(0);
            $table->boolean('discount_type')->default(true)->comment('0 = Fix | 1 = Percentage');
            $table->double('discount_amount')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_tier_pricings');
    }
};
