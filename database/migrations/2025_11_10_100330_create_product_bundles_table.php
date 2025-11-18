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
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('source_product_id');
            $table->unsignedBigInteger('source_variant_id')->nullable();
            $table->double('quantity')->default(1);
            $table->boolean('unit_type')->default(0)->comment('0 = Base Unit | 1 = Additional Unit');
            $table->unsignedBigInteger('unit_id')->comment('product_base_units.id or product_additional_units.id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
    }
};
