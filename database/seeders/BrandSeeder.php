<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
                'url' => 'https://en.wikipedia.org/wiki/Nestl%C3%A9#/media/File:Nestl%C3%A9.svg',
                'status' => 1,
            ],
            [
                'name' => 'PepsiCo',
                'slug' => Str::slug('PepsiCo'),
                'description' => 'Multinational food, snack, and beverage corporation best known for Pepsi, Lay’s, and Gatorade.',
                'url' => 'https://en.wikipedia.org/wiki/PepsiCo#/media/File:PepsiCo_(2025,_wordmark).svg',
                'status' => 1,
            ],
            [
                'name' => 'Heineken',
                'slug' => Str::slug('Heineken'),
                'description' => 'Dutch brewing company producing premium lagers and other alcoholic beverages.',
                'url' => 'https://en.wikipedia.org/wiki/Heineken#/media/File:Heineken_logo.svg',
                'status' => 1,
            ],
            [
                'name' => 'Coca-Cola',
                'slug' => Str::slug('Coca-Cola'),
                'description' => 'World’s leading beverage company with a wide portfolio of soft drinks and juices.',
                'url' => 'https://en.wikipedia.org/wiki/Coca-Cola#/media/File:Coca-Cola_logo.svg',
                'status' => 1,
            ],
            [
                'name' => 'AB InBev',
                'slug' => Str::slug('AB InBev'),
                'description' => 'Largest beer company globally with brands like Budweiser, Corona, and Stella Artois.',
                'url' => 'https://en.wikipedia.org/wiki/AB_InBev#/media/File:Anheuser-Busch_InBev_-_logo_(Belgium,_2022-).svg',
                'status' => 1,
            ],
            [
                'name' => 'Dr Pepper',
                'slug' => Str::slug('Dr Pepper'),
                'description' => 'Dr Pepper is a carbonated soft drink',
                'url' => 'https://en.wikipedia.org/wiki/Dr_Pepper#/media/File:Dr_Pepper_modern.svg',
                'status' => 1,
            ],
            [
                'name' => 'Apple',
                'slug' => Str::slug('Apple'),
                'description' => 'Apple Inc. is an American multinational technology company',
                'url' => 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg',
                'status' => 1,
            ],
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->updateOrInsert(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'description' => $brand['description'],
                    'status' => 1,
                ]
            );
        }
    }
}
