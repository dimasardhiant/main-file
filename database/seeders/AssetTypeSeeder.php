<?php

namespace Database\Seeders;

use App\Models\AssetType;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetTypeSeeder extends Seeder
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

        // Fixed asset types for consistent data
        $assetTypes = [
            [
                'name' => 'Computer Hardware',
                'description' => 'Desktop computers, laptops, servers, and other computing equipment used for business operations'
            ],
            [
                'name' => 'Mobile Devices',
                'description' => 'Smartphones, tablets, and other portable electronic devices assigned to employees'
            ],
            [
                'name' => 'Office Equipment',
                'description' => 'Printers, scanners, photocopiers, fax machines, and other office machinery'
            ],
            [
                'name' => 'Furniture',
                'description' => 'Office desks, chairs, cabinets, conference tables, and other workplace furniture'
            ],
            [
                'name' => 'Vehicles',
                'description' => 'Company cars, trucks, vans, and other vehicles used for business purposes'
            ],
            [
                'name' => 'Software Licenses',
                'description' => 'Software applications, operating systems, and digital licenses for business use'
            ],
            [
                'name' => 'Network Equipment',
                'description' => 'Routers, switches, modems, access points, and other networking hardware'
            ],
            [
                'name' => 'Audio Visual Equipment',
                'description' => 'Projectors, monitors, speakers, cameras, and presentation equipment'
            ],
            [
                'name' => 'Security Equipment',
                'description' => 'CCTV cameras, access control systems, alarms, and security devices'
            ],
            [
                'name' => 'Tools and Machinery',
                'description' => 'Specialized tools, machinery, and equipment specific to business operations'
            ],
            [
                'name' => 'Communication Equipment',
                'description' => 'Telephone systems, headsets, video conferencing equipment, and communication devices'
            ],
            [
                'name' => 'Storage Equipment',
                'description' => 'Filing cabinets, safes, storage units, and data storage devices'
            ]
        ];

        foreach ($companies as $company) {
            foreach ($assetTypes as $assetTypeData) {
                // Check if asset type already exists for this company
                if (AssetType::where('name', $assetTypeData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    AssetType::create([
                        'name' => $assetTypeData['name'],
                        'description' => $assetTypeData['description'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create asset type: ' . $assetTypeData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('AssetType seeder completed successfully!');
    }
}
