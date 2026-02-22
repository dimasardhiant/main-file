<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\EmailSettingController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SystemSettingsController;
use App\Http\Controllers\Settings\CurrencySettingController;
use App\Http\Controllers\PlanOrderController;
use App\Http\Controllers\Settings\PaymentSettingController;
use App\Http\Controllers\Settings\WebhookController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\PayPalPaymentController;
use App\Http\Controllers\BankPaymentController;
use App\Http\Controllers\Settings\WorkingDaysSettingController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
|
| Here are the routes for settings management
|
*/

// Payment routes accessible without plan check
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payment-methods', [PaymentSettingController::class, 'getPaymentMethods'])->name('payment.methods');
    Route::get('/enabled-payment-methods', [PaymentSettingController::class, 'getEnabledMethods'])->name('payment.enabled-methods');
    Route::post('/plan-orders', [PlanOrderController::class, 'create'])->name('plan-orders.create');
    Route::post('/stripe-payment', [StripePaymentController::class, 'processPayment'])->name('settings.stripe.payment');
});

Route::middleware(['auth', 'verified', 'plan.access'])->group(function () {
    // Payment Settings (admin only)
    Route::post('/payment-settings', [PaymentSettingController::class, 'store'])->name('payment.settings');

    // Profile settings page with profile and password sections
    Route::get('profile', function () {
        return Inertia::render('settings/profile-settings');
    })->name('profile');

    // Routes for form submissions
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile', [ProfileController::class, 'update']); // For file uploads with method spoofing
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('profile/password', [PasswordController::class, 'update'])->name('password.update');

    // Email settings page
    Route::get('settings/email', function () {
        return Inertia::render('settings/components/email-settings');
    })->name('settings.email');

    // Email settings routes
    Route::get('settings/email/get', [EmailSettingController::class, 'getEmailSettings'])->name('settings.email.get');
    Route::post('settings/email/update', [EmailSettingController::class, 'updateEmailSettings'])->name('settings.email.update');
    Route::post('settings/email/test', [EmailSettingController::class, 'sendTestEmail'])->name('settings.email.test');

    // General settings page with system and company settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');

    // System Settings routes
    Route::post('settings/system', [SystemSettingsController::class, 'update'])->name('settings.system.update');
    Route::post('settings/brand', [SystemSettingsController::class, 'updateBrand'])->name('settings.brand.update');
    Route::post('settings/storage', [SystemSettingsController::class, 'updateStorage'])->name('settings.storage.update');
    Route::post('settings/recaptcha', [SystemSettingsController::class, 'updateRecaptcha'])->name('settings.recaptcha.update');
    Route::post('settings/chatgpt', [SystemSettingsController::class, 'updateChatgpt'])->name('settings.chatgpt.update');
    Route::post('settings/cookie', [SystemSettingsController::class, 'updateCookie'])->name('settings.cookie.update');
    Route::post('settings/seo', [SystemSettingsController::class, 'updateSeo'])->name('settings.seo.update');
    Route::post('settings/cache/clear', [SystemSettingsController::class, 'clearCache'])->name('settings.cache.clear');

    // Currency Settings routes
    Route::post('settings/currency', [CurrencySettingController::class, 'update'])->name('settings.currency.update');

    // Working Days Settings routes
    Route::get('settings/working-days/get', [WorkingDaysSettingController::class, 'getWorkingDaysSettings'])->name('settings.working-days.get');
    Route::post('settings/working-days/update', [WorkingDaysSettingController::class, 'updateWorkingDaysSettings'])->name('settings.working-days.update');

    // Webhook Settings routes
    Route::get('settings/webhooks', [WebhookController::class, 'index'])->name('settings.webhooks.index');
    Route::post('settings/webhooks', [WebhookController::class, 'store'])->name('settings.webhooks.store');
    Route::put('settings/webhooks/{webhook}', [WebhookController::class, 'update'])->name('settings.webhooks.update');
    Route::delete('settings/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('settings.webhooks.destroy');

    // Google Calendar Settings routes
    Route::post('settings/google-calendar', [SystemSettingsController::class, 'updateGoogleCalendar'])->name('settings.google-calendar.update');

    // IP Restriction Settings routes
    Route::middleware('permission:manage-ip-restriction-settings')->group(function () {
        Route::post('ip-restrictions', [\App\Http\Controllers\IpRestrictionController::class, 'store'])->name('ip-restrictions.store');
        Route::put('ip-restrictions/{ipRestriction}', [\App\Http\Controllers\IpRestrictionController::class, 'update'])->name('ip-restrictions.update');
        Route::delete('ip-restrictions/{ipRestriction}', [\App\Http\Controllers\IpRestrictionController::class, 'destroy'])->name('ip-restrictions.destroy');
    });

    // Zekto Settings routes
    Route::middleware('permission:manage-biomatric-attedance-settings')->group(function () {
        Route::post('settings/zekto/update', [\App\Http\Controllers\ZektoSettingsController::class, 'update'])->name('settings.zekto.update');
        Route::post('settings/zekto/generate-token', [\App\Http\Controllers\ZektoSettingsController::class, 'generateToken'])->name('settings.zekto.generate-token');
    });
});
