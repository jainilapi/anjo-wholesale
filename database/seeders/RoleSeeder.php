<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'is_internal_role' => 1,
                'is_sytem_role' => 1
            ],
            [
                'name' => 'Customer',
                'slug' => 'customer',
                'is_internal_role' => 0,
                'is_sytem_role' => 1
            ],
            [
                'name' => 'Warehouse Manager',
                'slug' => 'warehouse-manager',
                'is_internal_role' => 1,
                'is_sytem_role' => 1
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'is_internal_role' => 1,
                'is_sytem_role' => 1
            ],
            [
                'name' => 'Supplier',
                'slug' => 'supplier',
                'is_internal_role' => 0,
                'is_sytem_role' => 1
            ]
        ];

        foreach ($roles as $role) {
            \Spatie\Permission\Models\Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
