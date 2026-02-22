<?php

namespace Database\Seeders;

use App\Models\JobLocation;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = User::where('type', 'company')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }
        
        // Fixed job locations for consistent data
        $jobLocations = [
            [
                'name' => 'Head Office - Mumbai',
                'address' => 'Bandra Kurla Complex, Bandra East',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'postal_code' => '400051',
                'is_remote' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Branch Office - Delhi',
                'address' => 'Connaught Place, Central Delhi',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'country' => 'India',
                'postal_code' => '110001',
                'is_remote' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Tech Hub - Bangalore',
                'address' => 'Electronic City Phase 1',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'country' => 'India',
                'postal_code' => '560100',
                'is_remote' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Development Center - Pune',
                'address' => 'Hinjewadi IT Park Phase 2',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'country' => 'India',
                'postal_code' => '411057',
                'is_remote' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Regional Office - Chennai',
                'address' => 'OMR IT Corridor, Thoraipakkam',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'country' => 'India',
                'postal_code' => '600097',
                'is_remote' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Service Center - Hyderabad',
                'address' => 'HITEC City, Madhapur',
                'city' => 'Hyderabad',
                'state' => 'Telangana',
                'country' => 'India',
                'postal_code' => '500081',
                'is_remote' => false,
                'status' => 'active'
            ],
            [
                'name' => 'Remote Work - India',
                'address' => null,
                'city' => null,
                'state' => null,
                'country' => 'India',
                'postal_code' => null,
                'is_remote' => true,
                'status' => 'active'
            ],
            [
                'name' => 'Remote Work - Global',
                'address' => null,
                'city' => null,
                'state' => null,
                'country' => null,
                'postal_code' => null,
                'is_remote' => true,
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($jobLocations as $locationData) {
                // Check if job location already exists for this company
                if (JobLocation::where('name', $locationData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    JobLocation::create([
                        'name' => $locationData['name'],
                        'address' => $locationData['address'],
                        'city' => $locationData['city'],
                        'state' => $locationData['state'],
                        'country' => $locationData['country'],
                        'postal_code' => $locationData['postal_code'],
                        'is_remote' => $locationData['is_remote'],
                        'status' => $locationData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create job location: ' . $locationData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('JobLocation seeder completed successfully!');
    }
}