<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view-department',
            'create-department',
            'edit-department',
            'delete-department',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
                'module' => 'department',
                'label' => ucfirst(str_replace('-', ' ', $permission)),
                'description' => 'Ability to ' . str_replace('-', ' ', $permission),
            ]);
        }
    }
}