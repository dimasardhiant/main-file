<?php

namespace Database\Seeders;

use App\Models\Webhook;
use App\Models\User;
use Illuminate\Database\Seeder;

class WebhookSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('type', 'company')->get();

        if ($users->isEmpty()) {
            $this->command->warn('No company users found. Please seed users first.');
            return;
        }

        $webhookTemplates = [
            ['module' => 'New User', 'method' => 'POST', 'url' => 'https://example.com/webhooks/new-user'],
            ['module' => 'New Appointment', 'method' => 'POST', 'url' => 'https://example.com/webhooks/new-appointment'],
            ['module' => 'New User', 'method' => 'GET', 'url' => 'https://example.com/api/user-created'],
            ['module' => 'New Appointment', 'method' => 'GET', 'url' => 'https://example.com/webhooks/appointment-status']
        ];

        foreach ($users as $user) {
            foreach ($webhookTemplates as $template) {
                Webhook::firstOrCreate(
                    ['user_id' => $user->id, 'module' => $template['module']],
                    array_merge($template, ['user_id' => $user->id])
                );
            }
        }

        $this->command->info('Webhooks seeded successfully!');
    }
}
