<?php

namespace Database\Seeders;

use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class PayoutRequestSeeder extends Seeder
{
    public function run(): void
    {
        if (isSaas()) {
            $companies = User::where('type', 'company')->take(4)->get();

            if ($companies->isEmpty()) {
                $this->command->warn('No company users found. Please seed users first.');
                return;
            }

            $payoutRequests = [
                [
                    'company_id' => $companies->first()->id,
                    'amount' => 250.00,
                    'status' => 'pending',
                    'notes' => 'Monthly commission payout request'
                ],
                [
                    'company_id' => $companies->skip(1)->first()->id,
                    'amount' => 150.75,
                    'status' => 'approved',
                    'notes' => 'Referral commission for Q1'
                ],
                [
                    'company_id' => $companies->skip(2)->first()->id,
                    'amount' => 500.00,
                    'status' => 'rejected',
                    'notes' => 'Insufficient commission balance'
                ],
                [
                    'company_id' => $companies->last()->id,
                    'amount' => 75.50,
                    'status' => 'pending',
                    'notes' => 'Weekly payout request'
                ]
            ];

            foreach ($payoutRequests as $requestData) {
                PayoutRequest::create($requestData);
            }

            $this->command->info('Payout requests seeded successfully!');
        }
    }
}
