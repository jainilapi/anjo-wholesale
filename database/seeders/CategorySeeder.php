<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Food', 'parent_id' => null, 'tags' => json_encode(['edibles', 'grocery', 'packaged']), 'description' => 'General food products and ingredients.', 'status' => 1],
            ['name' => 'Drinks', 'parent_id' => null, 'tags' => json_encode(['beverages', 'non-alcoholic']), 'description' => 'Soft drinks, water, and juices.', 'status' => 1],
            ['name' => 'Alcohol', 'parent_id' => null, 'tags' => json_encode(['beer', 'wine', 'spirits']), 'description' => 'All types of alcoholic beverages.', 'status' => 1],
            ['name' => 'Electronics', 'parent_id' => null, 'tags' => json_encode(['electronics', 'techonology', 'devices']), 'description' => 'All types of electronics.', 'status' => 1],
        ];

        DB::table('categories')->insert($categories);

        $foodId = DB::table('categories')->where('name', 'Food')->value('id');
        $drinkId = DB::table('categories')->where('name', 'Drinks')->value('id');
        $alcoholId = DB::table('categories')->where('name', 'Alcohol')->value('id');
        $electronicId = DB::table('categories')->where('name', 'Electronics')->value('id');

        $subcategories = [
            ['name' => 'Snacks', 'parent_id' => $foodId, 'tags' => json_encode(['chips', 'biscuits', 'crisps']), 'description' => 'Packaged and ready-to-eat snacks.', 'status' => 1],
            ['name' => 'Dairy', 'parent_id' => $foodId, 'tags' => json_encode(['milk', 'cheese', 'butter']), 'description' => 'Dairy products and alternatives.', 'status' => 1],
            ['name' => 'Frozen Foods', 'parent_id' => $foodId, 'tags' => json_encode(['frozen', 'ready meals']), 'description' => 'Frozen meals and ingredients.', 'status' => 1],

            ['name' => 'Soft Drinks', 'parent_id' => $drinkId, 'tags' => json_encode(['soda', 'cola', 'carbonated']), 'description' => 'Non-alcoholic carbonated beverages.', 'status' => 1],
            ['name' => 'Juices', 'parent_id' => $drinkId, 'tags' => json_encode(['fruit', 'smoothie', 'natural']), 'description' => 'Fruit and vegetable juice drinks.', 'status' => 1],
            ['name' => 'Water', 'parent_id' => $drinkId, 'tags' => json_encode(['mineral', 'sparkling']), 'description' => 'Bottled still and sparkling water.', 'status' => 1],

            ['name' => 'Beer', 'parent_id' => $alcoholId, 'tags' => json_encode(['lager', 'ale', 'stout']), 'description' => 'Various types of beers.', 'status' => 1],
            ['name' => 'Wine', 'parent_id' => $alcoholId, 'tags' => json_encode(['red', 'white', 'rose']), 'description' => 'Red, white, and rosé wines.', 'status' => 1],
            ['name' => 'Spirits', 'parent_id' => $alcoholId, 'tags' => json_encode(['vodka', 'rum', 'whisky']), 'description' => 'Distilled alcoholic beverages.', 'status' => 1],

            ['name' => 'Smartphone', 'parent_id' => $electronicId, 'tags' => json_encode(['smartphone', 'mobile', 'phone']), 'description' => 'Smartphones', 'status' => 1],
            ['name' => 'Television', 'parent_id' => $electronicId, 'tags' => json_encode(['television', 'tv', 'lcd', 'led']), 'description' => 'Televisions', 'status' => 1],
            ['name' => 'Laptop', 'parent_id' => $electronicId, 'tags' => json_encode(['laptop', 'computer', 'pc']), 'description' => 'Laptops.', 'status' => 1],
        ];

        DB::table('categories')->insert($subcategories);
    }
}
