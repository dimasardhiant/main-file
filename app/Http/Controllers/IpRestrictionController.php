<?php

namespace App\Http\Controllers;

use App\Models\IpRestriction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IpRestrictionController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->can('create-ip-restriction')) {

            $request->validate([
                'ip_address' => 'required|unique:ip_restrictions,ip_address',
            ]);

            IpRestriction::create([
                'ip_address' => $request->ip_address,
                'created_by' => Auth::id(),
            ]);

            return redirect()->back()->with('success', __('IP Address Added Successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function update(Request $request, IpRestriction $ipRestriction)
    {
        if (Auth::user()->can('edit-ip-restriction')) {
            $request->validate([
                'ip_address' => 'required|unique:ip_restrictions,ip_address,'.$ipRestriction->id,
            ]);

            $ipRestriction->update([
                'ip_address' => $request->ip_address,
            ]);

            return redirect()->back()->with('success', __('IP Address Update Successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function destroy(IpRestriction $ipRestriction)
    {
        if (Auth::user()->can('delete-ip-restriction')) {
            $ipRestriction->delete();

            return redirect()->back()->with('success', __('IP Address Delete Successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
