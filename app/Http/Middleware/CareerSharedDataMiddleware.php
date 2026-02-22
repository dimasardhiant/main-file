<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CareerSharedDataMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $userSlug = $request->route('userSlug');
        $userId = $this->getUserIdFromSlug($userSlug);

        if (! $userId) {
            abort(403, 'Company not found');
        }

        $companySettings = $this->getCompanySettings($userId);
        $companyUser = User::find($userId);
        if ($companyUser) {
            $companySettings = array_merge($companySettings, [
                'company_name' => $companyUser->name,
                'company_email' => $companyUser->email,
            ]);
        }

        // Add to request for controller access
        $request->merge([
            'companyId' => $userId,
            'userSlug' => $userSlug,
            'companySettings' => $companySettings,
        ]);

        Inertia::share([
            'companySettings' => $companySettings,
            'userSlug' => $userSlug,
            'companyId' => $userId,
        ]);

        return $next($request);
    }

    private function getUserIdFromSlug($userSlug)
    {
        if ($userSlug) {
            $user = User::where('slug', $userSlug)->first();
            if ($user) {
                $userId = getCompanyId($user->id);

                return $userId;
            }

            return null;
        }

        return null;
    }

    private function getCompanySettings($userId)
    {
        if (! $userId) {
            return [];
        }

        $settings = settings($userId);
        $user = User::find($userId);

        if ($user) {
            $settings['company_name'] = $user->name;
            $settings['company_email'] = $user->email;
        }

        return $settings;
    }
}
