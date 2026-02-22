<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AssetTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-asset-types')) {
            $query = AssetType::withCount('assets')->where(function ($q) {
                if (Auth::user()->can('manage-any-asset-types')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-asset-types')) {
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

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('name', 'asc');
            }

            $assetTypes = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/assets/types/index', [
                'assetTypes' => $assetTypes,
                'filters' => $request->all(['search', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        AssetType::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Asset type created successfully'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AssetType $assetType)
    {
        // Check if asset type belongs to current company
        if (!in_array($assetType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this asset type'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $assetType->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', __('Asset type updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AssetType $assetType)
    {
        // Check if asset type belongs to current company
        if (!in_array($assetType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this asset type'));
        }

        // Check if asset type is being used by any assets
        if ($assetType->assets()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete asset type that is being used by assets'));
        }

        $assetType->delete();

        return redirect()->back()->with('success', __('Asset type deleted successfully'));
    }
}
