<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Setting::doesntExist()) {
            Setting::create([
                'name' => 'Anjo Wholesale',
                'logo' => 'logo.png',
                'favicon' => 'favicon.ico'
            ]);
        }
    }
}
