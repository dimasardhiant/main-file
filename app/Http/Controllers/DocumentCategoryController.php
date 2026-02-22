<?php

namespace App\Http\Controllers;

use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class DocumentCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-document-categories')) {
            $query = DocumentCategory::withCount('documents')->where(function ($q) {
                if (Auth::user()->can('manage-any-document-categories')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-document-categories')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('is_mandatory') && $request->is_mandatory !== 'all') {
                $query->where('is_mandatory', $request->is_mandatory === 'true');
            }

            $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc');
            $documentCategories = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/documents/document-categories/index', [
                'documentCategories' => $documentCategories,
                'filters' => $request->all(['search', 'status', 'is_mandatory', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'required|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_mandatory' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DocumentCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Document category created successfully'));
    }

    public function update(Request $request, DocumentCategory $documentCategory)
    {
        if (!in_array($documentCategory->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this category'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'required|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_mandatory' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $documentCategory->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Document category updated successfully'));
    }

    public function destroy(DocumentCategory $documentCategory)
    {
        if (!in_array($documentCategory->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this category'));
        }

        if ($documentCategory->documents()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete category as it contains documents'));
        }

        $documentCategory->delete();
        return redirect()->back()->with('success', __('Document category deleted successfully'));
    }

    public function toggleStatus(DocumentCategory $documentCategory)
    {
        if (!in_array($documentCategory->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this category'));
        }

        $documentCategory->update([
            'status' => $documentCategory->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Category status updated successfully'));
    }
}
