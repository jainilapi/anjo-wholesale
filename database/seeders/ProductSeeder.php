<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $simpleProducts = [
            [
                'name' => 'Lay’s Classic Salted Chips 150g',
                'sku' => 'LAYS-150G',
                'short_description' => 'Crispy potato chips with a hint of salt.',
                'long_description' => 'Lay’s Classic Salted Chips are made from the finest potatoes and seasoned to perfection for a timeless taste.',
                'type' => 'simple',
                'single_product_price' => 1.25,
                'tags' => ['snack', 'chips', 'crisps'],
                'category' => 'Snacks',
                'brand' => 'pepsico',
                'unit' => 'Gram (g)',
                'image' => 'lays-150g.jpg',
            ],
            [
                'name' => 'Nestlé Pure Life Water 1L Bottle',
                'sku' => 'NESTLE-1L',
                'short_description' => 'Clean, purified bottled water for everyday hydration.',
                'long_description' => 'Nestlé Pure Life Water goes through a rigorous purification process to ensure great taste and safety.',
                'type' => 'simple',
                'single_product_price' => 0.60,
                'tags' => ['water', 'hydration'],
                'category' => 'Water',
                'brand' => 'nestle',
                'unit' => 'Bottle',
                'image' => 'nestle-water-1l.jpg',
            ],
            [
                'name' => 'Coca-Cola',
                'sku' => 'CC-VAR',
                'short_description' => 'Classic Coca-Cola soft drink.',
                'long_description' => 'Enjoy the world’s most loved carbonated beverage in multiple packaging sizes.',
                'type' => 'simple',
                'single_product_price' => null,
                'tags' => ['cola', 'soft drink'],
                'category' => 'Soft Drinks',
                'brand' => 'coca-cola',
                'unit' => 'Can',
                'image' => 'coca-cola-main.jpg',
            ],
            [
                'name' => 'Heineken Party Pack (6 x 330mL Bottles)',
                'sku' => 'HEI-BUNDLE-6PK',
                'short_description' => 'Six-pack of Heineken premium lager bottles.',
                'long_description' => 'Ideal for parties and events — contains 6 x 330mL bottles of Heineken Lager.',
                'type' => 'simple',
                'single_product_price' => 9.90,
                'tags' => ['beer', 'bundle', 'party pack'],
                'category' => 'Beer',
                'brand' => 'heineken',
                'unit' => 'Case',
                'image' => 'heineken-bundle-6pk.jpg',
            ]
        ];

        foreach ($simpleProducts as $product) {
            \App\Models\Product::create([
                'name' => $product['name'],
                'sku' => $product['sku'],
                'short_description' => $product['short_description'],
                'long_description' => $product['long_description'],
                'type' => $product['type'],
                'single_product_price' => $product['single_product_price'],
                'tags' => $product['tags'] ?? [],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
