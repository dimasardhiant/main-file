<?php

namespace Database\Seeders;

use App\Models\IpRestriction;
use App\Models\User;
use Illuminate\Database\Seeder;

class IpRestrictionSeeder extends Seeder
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

        // Sample IP addresses for demo data
        $ipAddresses = [
            '192.168.1.1',
            '192.168.1.100',
            '10.0.0.1',
            '172.16.0.1',
            '203.0.113.1',
            '198.51.100.1',
            '192.0.2.1',
            '203.0.113.100',
        ];

        foreach ($companies as $company) {
            // Check if company already has 5 or more IP restrictions
            $existingCount = IpRestriction::where('created_by', $company->id)->count();
            if ($existingCount >= 5) {
                continue;
            }

            // Create exactly 5 IP restrictions for each company
            $created = 0;
            $index = 0;

            while ($created < 5 && $index < count($ipAddresses)) {
                $ipAddress = $ipAddresses[$index];

                // Check if IP address already exists for this company
                if (! IpRestriction::where('ip_address', $ipAddress)->where('created_by', $company->id)->exists()) {
                    try {
                        IpRestriction::create([
                            'ip_address' => $ipAddress,
                            'created_by' => $company->id,
                        ]);
                        $created++;
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create IP restriction: '.$ipAddress.' for company: '.$company->name);
                    }
                }
                $index++;
            }
        }

        $this->command->info('IpRestriction seeder completed successfully!');
    }
}
