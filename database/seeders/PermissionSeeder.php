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
            ],

            [
                'name' => 'Customers Listing',
                'slug' => 'customers.index'
            ],
            [
                'name' => 'Customers Add',
                'slug' => 'customers.create'
            ],
            [
                'name' => 'Customers Save',
                'slug' => 'customers.store'
            ],
            [
                'name' => 'Customers Edit',
                'slug' => 'customers.edit'
            ],
            [
                'name' => 'Customers Update',
                'slug' => 'customers.update'
            ],
            [
                'name' => 'Customers View',
                'slug' => 'customers.show'
            ],
            [
                'name' => 'Customers Delete',
                'slug' => 'customers.destroy'
            ],

            [
                'name' => 'Locations Listing',
                'slug' => 'locations.index'
            ],
            [
                'name' => 'Locations Add',
                'slug' => 'locations.create'
            ],
            [
                'name' => 'Locations Save',
                'slug' => 'locations.store'
            ],
            [
                'name' => 'Locations Edit',
                'slug' => 'locations.edit'
            ],
            [
                'name' => 'Locations Update',
                'slug' => 'locations.update'
            ],
            [
                'name' => 'Locations View',
                'slug' => 'locations.show'
            ],
            [
                'name' => 'Locations Delete',
                'slug' => 'locations.destroy'
            ],

            [
                'name' => 'Warehouses Listing',
                'slug' => 'warehouses.index'
            ],
            [
                'name' => 'Warehouses Add',
                'slug' => 'warehouses.create'
            ],
            [
                'name' => 'Warehouses Save',
                'slug' => 'warehouses.store'
            ],
            [
                'name' => 'Warehouses Edit',
                'slug' => 'warehouses.edit'
            ],
            [
                'name' => 'Warehouses Update',
                'slug' => 'warehouses.update'
            ],
            [
                'name' => 'Warehouses View',
                'slug' => 'warehouses.show'
            ],
            [
                'name' => 'Warehouses Delete',
                'slug' => 'warehouses.destroy'
            ],

            [
                'name' => 'Categories Listing',
                'slug' => 'categories.index'
            ],
            [
                'name' => 'Categories Add',
                'slug' => 'categories.create'
            ],
            [
                'name' => 'Categories Save',
                'slug' => 'categories.store'
            ],
            [
                'name' => 'Categories Edit',
                'slug' => 'categories.edit'
            ],
            [
                'name' => 'Categories Update',
                'slug' => 'categories.update'
            ],
            [
                'name' => 'Categories View',
                'slug' => 'categories.show'
            ],
            [
                'name' => 'Categories Delete',
                'slug' => 'categories.destroy'
            ],

            [
                'name' => 'Products Listing',
                'slug' => 'products.index'
            ],
            [
                'name' => 'Products Add',
                'slug' => 'products.create'
            ],
            [
                'name' => 'Products Save',
                'slug' => 'products.store'
            ],
            [
                'name' => 'Products Edit',
                'slug' => 'products.edit'
            ],
            [
                'name' => 'Products Update',
                'slug' => 'products.update'
            ],
            [
                'name' => 'Products View',
                'slug' => 'products.show'
            ],
            [
                'name' => 'Products Delete',
                'slug' => 'products.destroy'
            ],

            [
                'name' => 'Brands Listing',
                'slug' => 'brands.index'
            ],
            [
                'name' => 'Brands Add',
                'slug' => 'brands.create'
            ],
            [
                'name' => 'Brands Save',
                'slug' => 'brands.store'
            ],
            [
                'name' => 'Brands Edit',
                'slug' => 'brands.edit'
            ],
            [
                'name' => 'Brands Update',
                'slug' => 'brands.update'
            ],
            [
                'name' => 'Brands View',
                'slug' => 'brands.show'
            ],
            [
                'name' => 'Brands Delete',
                'slug' => 'brands.destroy'
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }
    }
}
