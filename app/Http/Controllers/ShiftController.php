<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-shifts')) {
            $query = Shift::with(['creator'])->where(function ($q) {
                if (Auth::user()->can('manage-any-shifts')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-shifts')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle shift type filter
            if ($request->has('shift_type') && !empty($request->shift_type) && $request->shift_type !== 'all') {
                if ($request->shift_type === 'night') {
                    $query->where('is_night_shift', true);
                } else {
                    $query->where('is_night_shift', false);
                }
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $shifts = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/shifts/index', [
                'shifts' => $shifts,
                'filters' => $request->all(['search', 'status', 'shift_type', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_duration' => 'required|integer|min:0',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i',
            'grace_period' => 'required|integer|min:0',
            'is_night_shift' => 'boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = creatorId();
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['is_night_shift'] = $validated['is_night_shift'] ?? false;

        // Check if shift with same name already exists
        $exists = Shift::where('name', $validated['name'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('Shift with this name already exists.'));
        }

        Shift::create($validated);

        return redirect()->back()->with('success', __('Shift created successfully.'));
    }

    public function update(Request $request, $shiftId)
    {
        $shift = Shift::where('id', $shiftId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($shift) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'start_time' => 'required|date_format:H:i',
                    'end_time' => 'required|date_format:H:i',
                    'break_duration' => 'required|integer|min:0',
                    'break_start_time' => 'nullable|date_format:H:i',
                    'break_end_time' => 'nullable|date_format:H:i',
                    'grace_period' => 'required|integer|min:0',
                    'is_night_shift' => 'boolean',
                    'status' => 'nullable|in:active,inactive',
                ]);

                // Check if shift with same name already exists (excluding current)
                $exists = Shift::where('name', $validated['name'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->where('id', '!=', $shiftId)
                    ->exists();

                if ($exists) {
                    return redirect()->back()->with('error', __('Shift with this name already exists.'));
                }

                $shift->update($validated);

                return redirect()->back()->with('success', __('Shift updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update shift'));
            }
        } else {
            return redirect()->back()->with('error', __('Shift Not Found.'));
        }
    }

    public function destroy($shiftId)
    {
        $shift = Shift::where('id', $shiftId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($shift) {
            try {
                $shift->delete();
                return redirect()->back()->with('success', __('Shift deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete shift'));
            }
        } else {
            return redirect()->back()->with('error', __('Shift Not Found.'));
        }
    }

    public function toggleStatus($shiftId)
    {
        $shift = Shift::where('id', $shiftId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($shift) {
            try {
                $shift->status = $shift->status === 'active' ? 'inactive' : 'active';
                $shift->save();

                return redirect()->back()->with('success', __('Shift status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update shift status'));
            }
        } else {
            return redirect()->back()->with('error', __('Shift Not Found.'));
        }
    }
}
