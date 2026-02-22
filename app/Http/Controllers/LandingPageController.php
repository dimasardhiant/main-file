<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Plan;
use App\Models\LandingPageSetting;
use App\Models\LandingPageCustomPage;
use App\Models\Business;
use App\Models\contact;

class LandingPageController extends Controller
{
    public function show(Request $request)
    {
        $host = $request->getHost();
        $hostParts = explode('.', $host);
        
        // Track general landing page visit
        // \Shetabit\Visitor\Facade\Visitor::visit();
        
        // Check if landing page is enabled in settings
        if (!isLandingPageEnabled()) {
            return redirect()->route('login');
        }
        
        $landingSettings = LandingPageSetting::getSettings();
        
        $plans = collect();
        
        if (isSaas()) {
            $plans = Plan::where('is_plan_enable', 'on')->get()->map(function ($plan) {
                $features = [];
                if ($plan->enable_custdomain === 'on') $features[] = 'Custom Domain';
                if ($plan->enable_custsubdomain === 'on') $features[] = 'Subdomain';
                if ($plan->pwa_business === 'on') $features[] = 'PWA';
                if ($plan->enable_chatgpt === 'on') $features[] = 'AI Integration';
                
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'yearly_price' => $plan->yearly_price,
                    'duration' => $plan->duration,
                    'description' => $plan->description,
                    'features' => $features,
                    'stats' => [
                        'employees' => $plan->max_employees,
                        'users' => $plan->max_users,
                        'storage' => $plan->storage_limit . ' GB',
                    ],
                    'is_plan_enable' => $plan->is_plan_enable,
                    'is_popular' => false // Will be set based on subscriber count
                ];
            });
            
            // Mark most subscribed plan as popular
            $planSubscriberCounts = Plan::withCount('users')->get()->pluck('users_count', 'id');
            if ($planSubscriberCounts->isNotEmpty()) {
                $mostSubscribedPlanId = $planSubscriberCounts->keys()->sortByDesc(function($planId) use ($planSubscriberCounts) {
                    return $planSubscriberCounts[$planId];
                })->first();
                
                $plans = $plans->map(function($plan) use ($mostSubscribedPlanId) {
                    if ($plan['id'] == $mostSubscribedPlanId && $plan['price'] != '0') {
                        $plan['is_popular'] = true;
                    }
                    return $plan;
                });
            }
        }
        
        return Inertia::render('landing-page/index', [
            'plans' => $plans,
            'testimonials' => [],
            'faqs' => [],
            'customPages' => LandingPageCustomPage::active()->ordered()->get() ?? [],
            'settings' => $landingSettings
        ]);
    }

    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        $contact = new contact();
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->subject = $request->subject;
        $contact->message = $request->message;
        $contact->save();

        return back()->with('success', __('Thank you for your message. We will get back to you soon!'));
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        // For now, just return success - implement newsletter subscription later
        return back()->with('success', __('Thank you for subscribing to our newsletter!'));
    }

    public function settings()
    {
        $landingSettings = LandingPageSetting::getSettings();
        
        return Inertia::render('landing-page/settings', [
            'settings' => $landingSettings
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:255',
            'contact_address' => 'required|string|max:255',
            'config_sections' => 'required|array'
        ]);
        $landingSettings = LandingPageSetting::getSettings();
        $landingSettings->update($request->all());

        return back()->with('success', __('Landing page settings updated successfully!'));
    }
}