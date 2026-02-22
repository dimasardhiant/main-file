<?php

namespace App\Http\Controllers;

use App\Models\CustomQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomQuestionController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-custom-questions')) {
            $query = CustomQuestion::where(function ($q) {
                if (Auth::user()->can('manage-any-custom-questions')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-custom-questions')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && ! empty($request->search)) {
                $query->where('question', 'like', '%'.$request->search.'%');
            }

            // Handle sorting
            if ($request->has('sort_field') && ! empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $customQuestions = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/custom-questions/index', [
                'customQuestions' => $customQuestions,
                'filters' => $request->all(['search', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-custom-questions')) {
            try {
                $validated = $request->validate([
                    'question' => 'required|string',
                    'required' => 'required|integer|in:0,1',
                ]);

                $validated['created_by'] = creatorId();

                // Check if question already exists
                $exists = CustomQuestion::where('question', $validated['question'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->exists();

                if ($exists) {
                    return redirect()->back()->with('error', __('Custom question with this text already exists.'));
                }

                CustomQuestion::create($validated);

                return redirect()->back()->with('success', __('Custom question created successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to create custom question'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function update(Request $request, $customQuestionId)
    {
        if (Auth::user()->can('edit-custom-questions')) {
            $customQuestion = CustomQuestion::where('id', $customQuestionId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($customQuestion) {
                try {
                    $validated = $request->validate([
                        'question' => 'required|string',
                        'required' => 'required|integer|in:0,1',
                    ]);

                    // Check if question already exists (excluding current question)
                    $exists = CustomQuestion::where('question', $validated['question'])
                        ->whereIn('created_by', getCompanyAndUsersId())
                        ->where('id', '!=', $customQuestionId)
                        ->exists();

                    if ($exists) {
                        return redirect()->back()->with('error', __('Custom question with this text already exists.'));
                    }

                    $customQuestion->update($validated);

                    return redirect()->back()->with('success', __('Custom question updated successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update custom question'));
                }
            } else {
                return redirect()->back()->with('error', __('Custom question not found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function destroy($customQuestionId)
    {
        if (Auth::user()->can('delete-custom-questions')) {
            $customQuestion = CustomQuestion::where('id', $customQuestionId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($customQuestion) {
                try {
                    $customQuestion->delete();

                    return redirect()->back()->with('success', __('Custom question deleted successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete custom question'));
                }
            } else {
                return redirect()->back()->with('error', __('Custom question not found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
