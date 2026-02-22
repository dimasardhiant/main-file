<?php

namespace Database\Seeders;

use App\Models\Referral;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class ReferralSeeder extends Seeder
{
    public function run(): void
    {
        if (isSaas()) {
            $users = User::where('type', 'company')->get();
            $plans = Plan::take(3)->get();

            if ($users->isEmpty() || $plans->isEmpty()) {
                $this->command->warn('No users or plans found. Please seed users and plans first.');
                return;
            }

            $referrals = [
                [
                    'user_id' => $users->first()->id,
                    'company_id' => $users->skip(1)->first()->id,
                    'commission_percentage' => 10.00,
                    'amount' => 19.99,
                    'plan_id' => $plans->first()->id
                ],
                [
                    'user_id' => $users->skip(1)->first()->id,
                    'company_id' => $users->skip(2)->first()->id,
                    'commission_percentage' => 15.00,
                    'amount' => 49.99,
                    'plan_id' => $plans->skip(1)->first()->id
                ],
                [
                    'user_id' => $users->skip(2)->first()->id,
                    'company_id' => $users->skip(3)->first()->id,
                    'commission_percentage' => 12.50,
                    'amount' => 99.99,
                    'plan_id' => $plans->last()->id
                ],
                [
                    'user_id' => $users->skip(3)->first()->id,
                    'company_id' => $users->last()->id,
                    'commission_percentage' => 8.00,
                    'amount' => 19.99,
                    'plan_id' => $plans->first()->id
                ]
            ];

            foreach ($referrals as $referralData) {
                Referral::create($referralData);
            }

            $this->command->info('Referrals seeded successfully!');
        }
    }
}
