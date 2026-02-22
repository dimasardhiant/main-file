<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkingDaysSettingController extends Controller
{
    public function getWorkingDaysSettings()
    {
        $settings = settings();
        $workingDaysJson = isset($settings['working_days']) ? $settings['working_days'] : [];
        $workingDayIds = $workingDaysJson ? json_decode($workingDaysJson, true) : [];
        $settings = [];
        foreach ($workingDayIds as $dayId) {
            $dayName = strtolower(\Carbon\Carbon::create()->dayOfWeek($dayId)->format('l'));
            $settings["working_day_{$dayName}"] = true;
        }

        return response()->json($settings);
    }

    public function updateWorkingDaysSettings(Request $request)
    {
        if (Auth::user()->can('update-working-days-settings')) {
            $validated = $request->validate([
                'working_days' => 'required|array',
                'working_days.*' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            ]);

            $workingDayIds = [];
            foreach ($validated['working_days'] as $dayName) {
                $dayId = \Carbon\Carbon::parse($dayName)->dayOfWeek;
                $workingDayIds[] =  $dayId; // Convert Sunday from 0 to 7
            }

            updateSetting('working_days', json_encode($workingDayIds));

            return redirect()->back()->with('success', __('Working days settings updated successfully.'));
        }else{
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
