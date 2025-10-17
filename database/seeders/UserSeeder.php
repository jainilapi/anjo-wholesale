<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'roles' => [
                    'admin' => 1
                ],
                'user' => [
                    'name' => 'Super Admin',
                    'email' => 'admin@gmail.com',
                    'dial_code' => 91,
                    'status' => 1,
                    'phone_number' => 9999999999,
                    'password' => 12345678,
                    'added_by' => 1
                ]
            ]
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['dial_code' => $user['user']['dial_code'], 'phone_number' => $user['user']['phone_number']], $user['user'])->syncRoles($user['roles']);
        }
    }
}
