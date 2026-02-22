<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Services\EmailTemplateService;
use App\Services\WebhookService;
use Exception;

class SendUserCreatedEmail
{
    private static array $processedUsers = [];
    
    public function __construct(
        private EmailTemplateService $emailService,
        private WebhookService $webhookService
    ) {
    }

    public function handle(UserCreated $event): void
    {
        $user = $event->user;
        $plainPassword = $event->plainPassword;

        // Prevent duplicate processing
        $userKey = $user->id . '_' . $user->updated_at->timestamp;
        if (in_array($userKey, self::$processedUsers)) {
            return;
        }
        
        self::$processedUsers[] = $userKey;

        // Prepare email variables
        $variables = [
            '{app_url}' => config('app.url'),
            '{user_name}' => $user->name,
            '{user_email}' => $user->email,
            '{user_password}' => $plainPassword ?: 'Password set by user',
            '{user_type}' => ucfirst($user->type),
            '{app_name}' => config('app.name'),
            '{created_date}' => $user->created_at->format('Y-m-d H:i:s'),
        ];

        try {
            // Send welcome email to the newly created user in their language
            $userLanguage = $user->lang ?? 'en';
            $this->emailService->sendTemplateEmailWithLanguage(
                templateName: 'User Created',
                variables: $variables,
                toEmail: $user->email,
                toName: $user->name,
                language: $userLanguage
            );
            
            // Trigger webhooks for New User
            $this->webhookService->triggerWebhooks('New User', $user->toArray(), $user->created_by ?? $user->id);

        } catch (Exception $e) {
            // Store error in session for frontend notification
            session()->flash('email_error', 'Failed to send welcome email: ' . $e->getMessage());
        }
    }
}