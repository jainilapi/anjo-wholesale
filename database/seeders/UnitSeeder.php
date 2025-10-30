<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['title' => 'Kilogram (kg)', 'text' => 'Standard unit for solid food weight.'],
            ['title' => 'Gram (g)', 'text' => 'Used for small quantities of ingredients or snacks.'],
            ['title' => 'Liter (L)', 'text' => 'Standard volume unit for liquids like milk, juice, and water.'],
            ['title' => 'Milliliter (mL)', 'text' => 'Used for small volume packaging, e.g., 250mL juice.'],
            ['title' => 'Pack', 'text' => 'Generic packaging unit for grouped products.'],
            ['title' => 'Bottle', 'text' => 'Common unit for beverages and alcoholic drinks.'],
            ['title' => 'Can', 'text' => 'Used for soft drinks, beer, and other canned beverages.'],
            ['title' => 'Carton', 'text' => 'Packaging unit for milk, juice, or boxed items.'],
            ['title' => 'Case', 'text' => 'Bulk shipping unit typically containing multiple packs or bottles.'],
            ['title' => 'Piece', 'text' => 'Individual unit, often used for single items or bakery goods.'],
            ['title' => 'Pallet', 'text' => 'Pallet.'],
        ];

        DB::table('units')->insert($units);
    }
}
