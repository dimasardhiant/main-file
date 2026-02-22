<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Currency;
use App\Models\PaymentSetting;
use App\Models\Webhook;
use App\Models\IpRestriction;

class SettingsController extends Controller
{
    /**
     * Display the main settings page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Get system settings using helper function
        $systemSettings = settings();
        $currencies = Currency::all();
        $paymentSettings = PaymentSetting::getUserSettings(auth()->id());
        $webhooks = Webhook::where('user_id', auth()->id())->get();
        $ipRestrictions = IpRestriction::whereIn('created_by', getCompanyAndUsersId())->orderBy('id','desc')->get();

        // Get Zekto settings for company users
        $zektoSettings = [];
        $zektoSettings = [
            'zkteco_api_url' =>  isset($systemSettings['zkteco_api_url']) ? $systemSettings['zkteco_api_url'] : '',
            'zkteco_username' => isset($systemSettings['zkteco_username']) ? $systemSettings['zkteco_username'] : '',
            'zkteco_password' => isset($systemSettings['zkteco_password']) ? $systemSettings['zkteco_password'] : '',
            'zkteco_auth_token' => isset($systemSettings['zkteco_auth_token']) ? $systemSettings['zkteco_auth_token'] : '',
        ];


        return Inertia::render('settings/index', [
            'systemSettings' => $systemSettings,
            'settings' => $systemSettings, // For helper functions
            'cacheSize' => getCacheSize(),
            'currencies' => $currencies,
            'timezones' => config('timezones'),
            'dateFormats' => config('dateformat'),
            'timeFormats' => config('timeformat'),
            'paymentSettings' => $paymentSettings,
            'webhooks' => $webhooks,
            'zektoSettings' => $zektoSettings,
            'ipRestrictions' => $ipRestrictions,
        ]);
    }
}
