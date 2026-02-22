<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get all companies
        $companies = User::where('type', 'company')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }
        
        // Branch names array
        $branchNames = [
            'Main Office',
            'Downtown Branch',
            'North Branch',
            'South Branch',
            'East Branch',
            'West Branch',
            'Corporate Center',
            'Business Park',
            'City Center'
        ];
        
        foreach ($companies as $company) {
            // Create 8-9 branches for each company
            $branchCount = rand(8, 9);
            
            for ($i = 0; $i < $branchCount; $i++) {
                $branchName = $branchNames[$i];
                
                // Check if branch already exists for this company
                if (Branch::where('name', $branchName)->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    Branch::create([
                        'name' => $branchName,
                        'address' => $faker->streetAddress,
                        'city' => $faker->city,
                        'state' => $faker->state,
                        'country' => $faker->country,
                        'zip_code' => $faker->postcode,
                        'phone' => $faker->phoneNumber,
                        'email' => strtolower(str_replace(' ', '', $branchName)) . '@' . strtolower(str_replace(' ', '', $company->name)) . '.com',
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create branch: ' . $branchName . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('Branch seeder completed successfully!');
    }
}