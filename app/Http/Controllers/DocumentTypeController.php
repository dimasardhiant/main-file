<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DocumentTypeController extends Controller
{

    public function index(Request $request)
    {
        if (Auth::user()->can('manage-document-types')) {
            $query = DocumentType::where(function ($q) {
                if (Auth::user()->can('manage-any-document-types')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-document-types')) {
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

            // Handle required filter
            if ($request->has('required') && $request->required !== 'all') {
                $isRequired = $request->required === 'yes';
                $query->where('is_required', $isRequired);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $documentTypes = $query->paginate($request->per_page ?? 10);

            // Cast is_required to boolean for each document type
            $documentTypes->getCollection()->transform(function ($documentType) {
                $documentType->is_required = (bool) $documentType->is_required;
                return $documentType;
            });

            return Inertia::render('hr/document-types/index', [
                'documentTypes' => $documentTypes,
                'filters' => $request->all(['search', 'sort_field', 'sort_direction', 'per_page', 'required']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function store(Request $request)
    {
        if (Auth::user()->can('create-document-types')) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'is_required' => 'boolean',
                ]);

                $validated['created_by'] = creatorId();

                DocumentType::create($validated);
                return redirect()->back()->with('success', __('Document type created successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to create document type'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function update(Request $request, $documentTypeId)
    {
        if (Auth::user()->can('edit-document-types')) {
            $documentType = DocumentType::where('id', $documentTypeId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($documentType) {
                try {
                    $validated = $request->validate([
                        'name' => 'required|string|max:255',
                        'description' => 'nullable|string',
                        'is_required' => 'boolean',
                    ]);

                    $documentType->update($validated);
                    return redirect()->back()->with('success', __('Document type updated successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update document type'));
                }
            } else {
                return redirect()->back()->with('error', __('Document Type Not Found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function destroy($documentTypeId)
    {
        if (Auth::user()->can('delete-document-types')) {
            $documentType = DocumentType::where('id', $documentTypeId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($documentType) {
                try {
                    $documentType->delete();
                    return redirect()->back()->with('success', __('Document type deleted successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete document type'));
                }
            } else {
                return redirect()->back()->with('error', __('Document Type Not Found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
