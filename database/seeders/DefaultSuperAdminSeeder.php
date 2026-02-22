<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DefaultSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (config('app.is_saas') == true) {
            // Create Super Admin User
            $superAdmin = User::firstOrCreate(
                ['email' => 'superadmin@example.com'],
                [
                    'name' => 'Super Admin',
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'type' => 'superadmin',
                    'lang' => 'en'
                ]
            );

            // Assign super admin role
            $superAdmin->assignRole('superadmin');

            // Create default settings for superadmin if not exists
            if (!Setting::where('user_id', $superAdmin->id)->exists()) {
                createDefaultSettings($superAdmin->id);
            }
        }
    }
}
