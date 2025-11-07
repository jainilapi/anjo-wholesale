<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class WarehouseSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 10; $i++) {
            DB::table('warehouses')->insert([
                'code' => $faker->unique()->word,
                'name' => $faker->company,
                'address_line_1' => $faker->address,
                'address_line_2' => $faker->optional()->address,
                'country_id' => 101,
                'state_id' => 4030,
                'city_id' => 57606,
                'zipcode' => $faker->postcode,
                'email' => $faker->unique()->email,
                'contact_number' => $faker->phoneNumber,
                'fax' => $faker->optional()->phoneNumber,
                'latitude' => $faker->latitude,
                'longitude' => $faker->longitude,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
