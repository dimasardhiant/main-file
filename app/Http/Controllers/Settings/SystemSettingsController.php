<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\StorageConfigService;

class SystemSettingsController extends Controller
{
    public function update(Request $request)
    {
        try {
            $rules = [
                'defaultLanguage' => 'required|string',
                'dateFormat' => 'required|string',
                'timeFormat' => 'required|string',
                'calendarStartDay' => 'required|string',
                'defaultTimezone' => 'required|string',
                'emailVerification' => 'boolean',
                'landingPageEnabled' => 'boolean',
                'ipRestrictionEnabled'  => 'boolean',
            ];

            if(isSaaS()){
                $rules['termsConditionsUrl'] = 'nullable';
            } else {
                $rules['termsConditionsUrl'] = 'nullable';
            }

            $validated = $request->validate($rules);

            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('System settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update system settings: :error', ['error' => $e->getMessage()]));
        }
    }
    
    /**
     * Update the brand settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBrand(Request $request)
    {
        try {
            $validated = $request->validate([
                'settings' => 'required|array',
                'settings.logoDark' => 'nullable|string',
                'settings.logoLight' => 'nullable|string',
                'settings.favicon' => 'nullable|string',
                'settings.titleText' => 'nullable|string|max:255',
                'settings.footerText' => 'nullable|string|max:500',
                'settings.companyMobile' => 'nullable|string|max:20',
                'settings.themeColor' => 'nullable|string|in:blue,green,purple,orange,red,custom',
                'settings.customColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'settings.sidebarVariant' => 'nullable|string|in:inset,floating,minimal',
                'settings.sidebarStyle' => 'nullable|string|in:plain,colored,gradient',
                'settings.layoutDirection' => 'nullable|string|in:left,right',
                'settings.themeMode' => 'nullable|string|in:light,dark,system',
            ]);

            $userId = auth()->id();
            foreach ($validated['settings'] as $key => $value) {
                updateSetting($key, $value, $userId);
            }

            return redirect()->back()->with('success', __('Brand settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update brand settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the recaptcha settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRecaptcha(Request $request)
    {
        try {
            $validated = $request->validate([
                'recaptchaEnabled' => 'boolean',
                'recaptchaVersion' => 'required|in:v2,v3',
                'recaptchaSiteKey' => 'required|string',
                'recaptchaSecretKey' => 'required|string',
            ]);
            
            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('ReCaptcha settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update ReCaptcha settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the chatgpt settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateChatgpt(Request $request)
    {
        try {
            $validated = $request->validate([
                'chatgptKey' => 'required|string',
                'chatgptModel' => 'required|string',
            ]);
            
            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Chat GPT settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Chat GPT settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the storage settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStorage(Request $request)
    {
        try {
            $validated = $request->validate([
                'storage_type' => 'required|in:local,aws_s3,wasabi',
                'allowedFileTypes' => 'required|string',
                'maxUploadSize' => 'required|numeric|min:1',
                'awsAccessKeyId' => 'required_if:storage_type,aws_s3|string',
                'awsSecretAccessKey' => 'required_if:storage_type,aws_s3|string',
                'awsDefaultRegion' => 'required_if:storage_type,aws_s3|string',
                'awsBucket' => 'required_if:storage_type,aws_s3|string',
                'awsUrl' => 'required_if:storage_type,aws_s3|string',
                'awsEndpoint' => 'required_if:storage_type,aws_s3|string',
                'wasabiAccessKey' => 'required_if:storage_type,wasabi|string',
                'wasabiSecretKey' => 'required_if:storage_type,wasabi|string',
                'wasabiRegion' => 'required_if:storage_type,wasabi|string',
                'wasabiBucket' => 'required_if:storage_type,wasabi|string',
                'wasabiUrl' => 'required_if:storage_type,wasabi|string',
                'wasabiRoot' => 'required_if:storage_type,wasabi|string',
            ]);

            $userId = Auth::id();
            
            $settings = [
                'storage_type' => $validated['storage_type'],
                'storage_file_types' => $validated['allowedFileTypes'],
                'storage_max_upload_size' => $validated['maxUploadSize'],
            ];

            if ($validated['storage_type'] === 'aws_s3') {
                $settings['aws_access_key_id'] = $validated['awsAccessKeyId'];
                $settings['aws_secret_access_key'] = $validated['awsSecretAccessKey'];
                $settings['aws_default_region'] = $validated['awsDefaultRegion'];
                $settings['aws_bucket'] = $validated['awsBucket'];
                $settings['aws_url'] = $validated['awsUrl'];
                $settings['aws_endpoint'] = $validated['awsEndpoint'];
            }

            if ($validated['storage_type'] === 'wasabi') {
                $settings['wasabi_access_key'] = $validated['wasabiAccessKey'];
                $settings['wasabi_secret_key'] = $validated['wasabiSecretKey'];
                $settings['wasabi_region'] = $validated['wasabiRegion'];
                $settings['wasabi_bucket'] = $validated['wasabiBucket'];
                $settings['wasabi_url'] = $validated['wasabiUrl'];
                $settings['wasabi_root'] = $validated['wasabiRoot'];
            }
            
            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
            }

            StorageConfigService::clearCache();

            return redirect()->back()->with('success', __('Storage settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update storage settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the cookie settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCookie(Request $request)
    {
        try {
            $validated = $request->validate([
                'enableLogging' => 'required|boolean',
                'strictlyNecessaryCookies' => 'required|boolean',
                'cookieTitle' => 'required|string|max:255',
                'strictlyCookieTitle' => 'required|string|max:255',
                'cookieDescription' => 'required|string',
                'strictlyCookieDescription' => 'required|string',
                'contactUsDescription' => 'required|string',
                'contactUsUrl' => 'required|url',
            ]);
            
            foreach ($validated as $key => $value) {
                updateSetting($key, is_bool($value) ? ($value ? '1' : '0') : $value);
            }

            return redirect()->back()->with('success', __('Cookie settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update cookie settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the SEO settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSeo(Request $request)
    {
        try {
            $validated = $request->validate([
                'metaKeywords' => 'required|string|max:255',
                'metaDescription' => 'required|string|max:160',
                'metaImage' => 'required|string',
            ]);
            
            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('SEO settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update SEO settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the Google Calendar settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleCalendar(Request $request)
    {
        try {
            $validated = $request->validate([
                'googleCalendarEnabled' => 'boolean',
                'googleCalendarId' => 'nullable|string|max:255',
                'googleCalendarJson' => 'nullable|file|mimes:json|max:2048',
            ]);

            $settings = [
                'googleCalendarEnabled' => $validated['googleCalendarEnabled'] ?? false,
                'googleCalendarId' => $validated['googleCalendarId'] ?? '',
            ];

            // Handle JSON file upload
            if ($request->hasFile('googleCalendarJson')) {
                $file = $request->file('googleCalendarJson');
                $path = $file->store('google-calendar', 'public');
                $settings['googleCalendarJsonPath'] = $path;
            }

            foreach ($settings as $key => $value) {
                updateSetting($key, is_bool($value) ? ($value ? '1' : '0') : $value);
            }

            return redirect()->back()->with('success', __('Google Calendar settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Google Calendar settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the Google Wallet settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleWallet(Request $request)
    {
        try {
            $validated = $request->validate([
                'googleWalletIssuerId' => 'nullable|string|max:255',
                'googleWalletJson' => 'nullable|file|mimes:json|max:2048',
            ]);

            $settings = [
                'googleWalletIssuerId' => $validated['googleWalletIssuerId'] ?? '',
            ];

            // Handle JSON file upload
            if ($request->hasFile('googleWalletJson')) {
                $file = $request->file('googleWalletJson');
                $path = $file->store('google-wallet', 'public');
                $settings['googleWalletJsonPath'] = $path;
            }

            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Google Wallet settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Google Wallet settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Clear application cache.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache()
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            \Artisan::call('optimize:clear');

            return redirect()->back()->with('success', __('Cache cleared successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to clear cache: :error', ['error' => $e->getMessage()]));
        }
    }
}   