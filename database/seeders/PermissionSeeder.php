<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'Users Listing',
                'slug' => 'users.index'
            ],
            [
                'name' => 'Users Add',
                'slug' => 'users.create'
            ],
            [
                'name' => 'Users Save',
                'slug' => 'users.store'
            ],
            [
                'name' => 'Users Edit',
                'slug' => 'users.edit'
            ],
            [
                'name' => 'Users Update',
                'slug' => 'users.update'
            ],
            [
                'name' => 'Users View',
                'slug' => 'users.show'
            ],
            [
                'name' => 'Users Delete',
                'slug' => 'users.destroy'
            ],
            

            [
                'name' => 'Roles Listing',
                'slug' => 'roles.index'
            ],
            [
                'name' => 'Roles Add',
                'slug' => 'roles.create'
            ],
            [
                'name' => 'Roles Save',
                'slug' => 'roles.store'
            ],
            [
                'name' => 'Roles Edit',
                'slug' => 'roles.edit'
            ],
            [
                'name' => 'Roles Update',
                'slug' => 'roles.update'
            ],
            [
                'name' => 'Roles View',
                'slug' => 'roles.show'
            ],
            [
                'name' => 'Roles Delete',
                'slug' => 'roles.destroy'
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }
    }
}
