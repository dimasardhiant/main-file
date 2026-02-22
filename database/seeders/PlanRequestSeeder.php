<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PlanRequest;
use App\Models\User;
use App\Models\Plan;

class PlanRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (isSaas()) {
            $users = User::where('type', '=', 'company')->take(5)->get();
            $plans = Plan::take(3)->get();

            if ($users->count() > 0 && $plans->count() > 0) {
                foreach ($users as $user) {
                    PlanRequest::create([
                        'user_id' => $user->id,
                        'plan_id' => $plans->random()->id,
                        'status' => collect(['pending', 'approved', 'rejected'])->random(),
                        'message' => 'I would like to upgrade my plan to access more features.',
                    ]);
                }
            }
        }
    }
}
