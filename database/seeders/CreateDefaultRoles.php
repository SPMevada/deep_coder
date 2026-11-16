<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class CreateDefaultRoles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
            ],
        ];

        Role::upsert($data, ['name'], ['name', 'slug']);
    }
}
