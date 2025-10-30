<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use DB;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                $brands = [
            [
                'name' => 'Nestlé',
                'slug' => Str::slug('Nestlé'),
                'description' => 'Global food and beverage company producing dairy, coffee, and confectionery products.',
                'logo' => 'brands/nestle.png',
                'status' => 1,
            ],
            [
                'name' => 'PepsiCo',
                'slug' => Str::slug('PepsiCo'),
                'description' => 'Multinational food, snack, and beverage corporation best known for Pepsi, Lay’s, and Gatorade.',
                'logo' => 'brands/pepsico.png',
                'status' => 1,
            ],
            [
                'name' => 'Heineken',
                'slug' => Str::slug('Heineken'),
                'description' => 'Dutch brewing company producing premium lagers and other alcoholic beverages.',
                'logo' => 'brands/heineken.png',
                'status' => 1,
            ],
            [
                'name' => 'Coca-Cola',
                'slug' => Str::slug('Coca-Cola'),
                'description' => 'World’s leading beverage company with a wide portfolio of soft drinks and juices.',
                'logo' => 'brands/coca_cola.png',
                'status' => 1,
            ],
            [
                'name' => 'AB InBev',
                'slug' => Str::slug('AB InBev'),
                'description' => 'Largest beer company globally with brands like Budweiser, Corona, and Stella Artois.',
                'logo' => 'brands/ab_inbev.png',
                'status' => 1,
            ],
            [
                'name' => 'Mondelez International',
                'slug' => Str::slug('Mondelez International'),
                'description' => 'Producer of snacks and confectionery brands such as Oreo, Cadbury, and Toblerone.',
                'logo' => 'brands/mondelez.png',
                'status' => 1,
            ],
        ];

        DB::table('brands')->insert($brands);
    }
}
