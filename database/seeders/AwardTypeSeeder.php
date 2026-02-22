<?php

namespace Database\Seeders;

use App\Models\AwardType;
use App\Models\User;
use Illuminate\Database\Seeder;

class AwardTypeSeeder extends Seeder
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

        // Award types with realistic data
        $awardTypes = [
            ['name' => 'Employee of the Month', 'description' => 'Recognition for outstanding performance and dedication during a specific month'],
            ['name' => 'Employee of the Year', 'description' => 'Annual recognition for exceptional contribution and consistent excellence throughout the year'],
            ['name' => 'Excellence Award', 'description' => 'Recognition for achieving excellence in work quality, innovation, and professional standards'],
            ['name' => 'Innovation Award', 'description' => 'Recognition for creative thinking, innovative solutions, and process improvements'],
            ['name' => 'Leadership Award', 'description' => 'Recognition for exceptional leadership qualities, team management, and mentoring abilities'],
            ['name' => 'Customer Service Award', 'description' => 'Recognition for outstanding customer service, client satisfaction, and relationship management'],
            ['name' => 'Team Player Award', 'description' => 'Recognition for collaboration, teamwork, and positive contribution to team dynamics'],
            ['name' => 'Achievement Award', 'description' => 'Recognition for achieving specific goals, targets, or milestones in projects or performance'],
            ['name' => 'Long Service Award', 'description' => 'Recognition for loyalty, commitment, and long-term service to the organization'],
            ['name' => 'Safety Award', 'description' => 'Recognition for maintaining workplace safety standards and promoting safety awareness'],
            ['name' => 'Quality Award', 'description' => 'Recognition for maintaining high quality standards and continuous improvement in work processes'],
            ['name' => 'Sales Achievement Award', 'description' => 'Recognition for exceptional sales performance, revenue generation, and client acquisition']
        ];

        foreach ($companies as $company) {
            // Create 6-8 award types for each company
            $awardCount = rand(6, 8);

            for ($i = 0; $i < $awardCount; $i++) {
                $awardType = $awardTypes[$i];

                // Check if award type already exists for this company
                if (AwardType::where('name', $awardType['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    AwardType::create([
                        'name' => $awardType['name'],
                        'description' => $awardType['description'],
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create award type: ' . $awardType['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('AwardType seeder completed successfully!');
    }
}
