<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Faker\Factory as Faker;

class StaffRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get all company users
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run CompanySeeder first.');
            return;
        }
        
        // Define role templates with permissions
        $roleTemplates = [
            [
                'name' => 'manager',
                'label' => 'Manager',
                'description' => 'Manager has access to manage buissness',
                'permissions' =>  [
                    'manage-dashboard', 'view-dashboard', 
                    'manage-users', 'view-users', 'create-users', 'edit-users', 'reset-password-users', 'toggle-status-users',
                    'manage-roles', 'view-roles',
                    'manage-media', 'manage-own-media', 'view-media', 'create-media', 'edit-media',
                    'manage-calendar', 'view-calendar', 'manage-appointments', 'manage-own-appointments',
                ]
            ],
            [
                'name' => 'contentcreator',
                'label' => 'Content Creator',
                'description' => 'Content Creator has access to manage buissness',
                'permissions' =>  [
                    'view-dashboard',
                    'manage-own-media', 'view-media', 'create-media', 'edit-media', 'download-media',
                ]
            ],
            [
                'name' => 'supportagent',
                'label' => 'Support Agent',
                'description' => 'Support Agent has access to manage buissness',
                'permissions' =>  [
                    'view-dashboard',
                ]
            ]
        ];
        
        // Create roles and staff users for each company
        foreach ($companyUsers as $company) {
            // Create roles for each company
            foreach ($roleTemplates as $roleTemplate) {               
                $role = Role::firstOrCreate([
                    'name' => $roleTemplate['name'],
                    'label' => $roleTemplate['label'],
                    'description' => $roleTemplate['description'],
                    'guard_name' => 'web',
                    'created_by' => $company->id
                ]);
                
                // Get permissions for this role
                $permissions = $roleTemplate['permissions'];
                
                // Get permission objects
                $permissionObjects = Permission::whereIn('name', $permissions)->get();
                
                // Assign permissions to role
                $role->syncPermissions($permissionObjects);
                
                // Create 1-2 staff users for each role
                $staffCount = rand(1, 2);
                
                for ($i = 0; $i < $staffCount; $i++) {
                    $firstName = $faker->firstName;
                    $lastName = $faker->lastName;
                    $name = $firstName . ' ' . $lastName;
                    $email = strtolower($firstName . '.' . $lastName . '.' . $company->id . '@example.com');
                    
                    // Skip if user already exists
                    if (User::where('email', $email)->exists()) {
                        continue;
                    }
                    
                    // Create staff user
                    $staff = User::create([
                        'name' => $name,
                        'email' => $email,
                        'email_verified_at' => now(),
                        'password' => Hash::make('password'),
                        'type' => $roleTemplate['name'],
                        'lang' => $faker->randomElement(['en', 'es', 'fr', 'de']),
                        'created_by' => $company->id,
                        'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                    ]);
                    
                    // Assign role to staff
                    $staff->assignRole($role);
                }
            }
        }
        
        $this->command->info('Created staff roles and users successfully!');
    }
}