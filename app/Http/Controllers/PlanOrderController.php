<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\PlanOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PlanOrderController extends BaseController
{
    public function index(Request $request)
    {

        $query = PlanOrder::with(['user', 'plan', 'coupon', 'processedBy']);

        // For Company
        if (Auth::user()->hasRole('company')) {
            $query->where('user_id', Auth::user()->id);
        }

        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('plan', function ($planQuery) use ($search) {
                        $planQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply filters
        if ($request->has('status') && $request->status !== '' && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'ordered_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 10);
        $planOrders = $query->paginate($perPage);

        // Always use super admin currency for plan pricing
        $superAdmin = User::where('type', 'superadmin')->first();
        $superAdminSettings = settings($superAdmin->id);
        $currency = $superAdminSettings ? ($superAdminSettings['defaultCurrency'] ?? 'USD') : 'USD';
        $currencySymbol = '$';
        if (!empty($currency)) {
            $currencyData = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyData ? $currencyData->symbol : '$';
        }

        return Inertia::render('plans/plan-orders', [
            'planOrders' => $planOrders,
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_order', 'per_page']),
            'currency' => $currency,
            'currencySymbol' => $currencySymbol
        ]);
    }

    public function approve(PlanOrder $planOrder)
    {
        try {
            $planOrder->approve(Auth::id());

            return redirect()->back()->with('success', __('Plan order approved successfully!'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to approve plan order'));
        }
    }

    public function reject(Request $request, PlanOrder $planOrder)
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:500'
            ]);

            $planOrder->reject(Auth::id(), $request->notes);

            return redirect()->back()->with('success', __('Plan order rejected successfully!'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to reject plan order'));
        }
    }
}
