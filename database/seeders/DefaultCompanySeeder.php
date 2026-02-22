<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default companies array
        $defaultCompanies = [
            [
                'name' => 'TechCorp Solutions',
                'email' => 'admin@techcorp.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Digital Innovations Ltd',
                'email' => 'admin@digitalinnovations.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Global Systems Inc',
                'email' => 'admin@globalsystems.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Nexus Technologies',
                'email' => 'admin@nexustech.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Quantum Dynamics',
                'email' => 'admin@quantumdynamics.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Apex Industries',
                'email' => 'admin@apexindustries.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Stellar Enterprises',
                'email' => 'admin@stellarenterprises.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Phoenix Corporation',
                'email' => 'admin@phoenixcorp.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Infinity Solutions',
                'email' => 'admin@infinitysolutions.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Vortex Systems',
                'email' => 'admin@vortexsystems.com',
                'lang' => 'en',
            ],
            [
                'name' => 'Company',
                'email' => 'company@example.com',
                'lang' => 'en',
            ]
        ];



        // Filter companies based on demo config
        if (config('app.is_saas') == true) {
            // Get all plans
            $plans = Plan::all();
            if (config('app.is_demo') == true) {
                $companiesToCreate = $defaultCompanies;
            } else {
                $companiesToCreate = array_filter($defaultCompanies, function ($company) {
                    return $company['email'] === 'company@example.com';
                });
            }
        } else {
            // Non-SaaS: Only one company
            $companiesToCreate = [[
                'name' => 'Company',
                'email' => 'company@example.com',
                'lang' => 'en',
            ]];
        }


        // Create default companies
        if (config('app.is_saas') == true) {
            foreach ($companiesToCreate as $companyData) {
                // Skip if user already exists
                if (User::where('email', $companyData['email'])->exists()) {
                    continue;
                }

                // Create company user
                $user = User::create([
                    'name' => $companyData['name'],
                    'email' => $companyData['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'type' => 'company',
                    'lang' => $companyData['lang'],
                    'plan_id' => config('app.is_demo') ? $plans->random()->id : $plans->first()->id,
                    'referral_code' => rand(100000, 999999),
                    'created_at' => now(),
                    'created_by' => 1,
                ]);

                // Assign company role
                $user->assignRole('company');

                // Create default settings
                if (!Setting::where('user_id', $user->id)->exists()) {
                    copySettingsFromSuperAdmin($user->id);
                }
            }
        } else {
            foreach ($companiesToCreate as $companyData) {
                // Skip if user already exists
                if (User::where('email', $companyData['email'])->exists()) {
                    continue;
                }

                // Create company user (no plan_id for non-SaaS)
                $user = User::create([
                    'name' => $companyData['name'],
                    'email' => $companyData['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'type' => 'company',
                    'lang' => $companyData['lang'],                    
                    'created_at' => now(),
                ]);

                // Assign company role
                $user->assignRole('company');

                // Create default settings
                if (!Setting::where('user_id', $user->id)->exists()) {
                    copySettingsFromSuperAdmin($user->id);
                }
            }
        }

        $this->command->info('Created ' . count($companiesToCreate) . ' default companies successfully!');
    }
}
