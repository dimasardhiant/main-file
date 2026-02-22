<?php

namespace App\Http\Controllers;

use App\Models\CandidateSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CandidateSourceController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-candidate-sources')) {
            $query = CandidateSource::where(function ($q) {
                if (Auth::user()->can('manage-any-candidate-sources')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-candidate-sources')) {
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

            $query->orderBy('created_at', 'desc');
            $candidateSources = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/candidate-sources/index', [
                'candidateSources' => $candidateSources,
                'filters' => $request->all(['search', 'status', 'per_page']),
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
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        CandidateSource::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Candidate source created successfully'));
    }

    public function update(Request $request, CandidateSource $candidateSource)
    {
        if (!in_array($candidateSource->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this candidate source');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $candidateSource->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Candidate source updated successfully'));
    }

    public function destroy(CandidateSource $candidateSource)
    {
        if (!in_array($candidateSource->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to delete this candidate source');
        }

        if ($candidateSource->candidates()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete candidate source as it is being used by candidates'));
        }

        $candidateSource->delete();
        return redirect()->back()->with('success', __('Candidate source deleted successfully'));
    }

    public function toggleStatus(CandidateSource $candidateSource)
    {
        if (!in_array($candidateSource->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this candidate source');
        }

        $candidateSource->update([
            'status' => $candidateSource->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Candidate source status updated successfully'));
    }
}
