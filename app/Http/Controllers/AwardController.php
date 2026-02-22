<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\AwardType;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AwardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-awards')) {
            // $query = Award::withPermissionCheck()->with(['employee.employee', 'awardType']);
            $query = Award::with(['employee.employee', 'awardType'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-awards')) {
                        $q->whereIn('created_by',  getCompanyAndUsersId());
                    } elseif (Auth::user()->can('manage-own-awards')) {
                        $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($query) use ($request) {
                    $query->whereHas('employee', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhereHas('employee', function ($empQ) use ($request) {
                                $empQ->where('employee_id', 'like', '%' . $request->search . '%');
                            });
                    })
                        ->orWhereHas('awardType', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request->search . '%');
                        })
                        ->orWhere('gift', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Handle award type filter
            if ($request->has('award_type_id') && !empty($request->award_type_id)) {
                $query->where('award_type_id', $request->award_type_id);
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('award_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('award_date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $awards = $query->paginate($request->per_page ?? 10);

            // Get award types for filter dropdown
            $awardTypes = AwardType::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get();

            // // Get employees for filter dropdown
            // $employees = User::with('employee')
            //     ->where('type', 'employee')
            //     ->whereIn('created_by', getCompanyAndUsersId())
            //     ->where('status', 'active')
            //     ->select('id', 'name')
            //     ->get()
            //     ->map(function ($user) {
            //         return [
            //             'id' => $user->id,
            //             'name' => $user->name,
            //             'employee_id' => $user->employee->employee_id ?? ''
            //         ];
            //     });

            return Inertia::render('hr/awards/index', [
                'awards' => $awards,
                'awardTypes' => $awardTypes,
                'employees' => $this->getFilteredEmployees(),
                'filters' => $request->all(['search', 'award_type_id', 'employee_id', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-awards') && !Auth::user()->can('manage-any-awards')) {
            $employeeQuery->where(function ($q) {
                $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? '',
                ];
            });
        return $employees;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->can('create-awards')) {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id',
                'award_type_id' => 'required|exists:award_types,id',
                'award_date' => 'required|date',
                'gift' => 'nullable|string|max:255',
                'monetary_value' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'certificate' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Check if award type belongs to current company
            $awardType = AwardType::find($request->award_type_id);
            if (!$awardType || !in_array($awardType->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('Invalid award type selected'));
            }

            // Check if employee belongs to current company
            $user = User::where('id', $request->employee_id)
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();
            if (!$user) {
                return redirect()->back()->with('error', __('Invalid employee selected'));
            }

            $awardData = [
                'employee_id' => $request->employee_id,
                'award_type_id' => $request->award_type_id,
                'award_date' => $request->award_date,
                'gift' => $request->gift,
                'monetary_value' => $request->monetary_value,
                'description' => $request->description,
                'created_by' => creatorId(),
            ];

            // Handle certificate from media library
            if ($request->certificate) {
                $awardData['certificate'] = $request->certificate;
            }

            // Handle photo from media library
            if ($request->photo) {
                $awardData['photo'] = $request->photo;
            }

            Award::create($awardData);

            return redirect()->back()->with('success', __('Award created successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Award $award)
    {
        if (Auth::user()->can('edit-awards')) {
            // Check if award belongs to current company
            if (!in_array($award->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to update this award'));
            }

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id',
                'award_type_id' => 'required|exists:award_types,id',
                'award_date' => 'required|date',
                'gift' => 'nullable|string|max:255',
                'monetary_value' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
                'certificate' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Check if award type belongs to current company
            $awardType = AwardType::find($request->award_type_id);
            if (!$awardType || !in_array($awardType->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('Invalid award type selected'));
            }

            // Check if employee belongs to current company
            $user = User::where('id', $request->employee_id)
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();
            if (!$user) {
                return redirect()->back()->with('error', __('Invalid employee selected'));
            }

            $awardData = [
                'employee_id' => $request->employee_id,
                'award_type_id' => $request->award_type_id,
                'award_date' => $request->award_date,
                'gift' => $request->gift,
                'monetary_value' => $request->monetary_value,
                'description' => $request->description,
            ];

            // Handle certificate from media library
            if ($request->certificate) {
                $awardData['certificate'] = $request->certificate;
            }

            // Handle photo from media library
            if ($request->photo) {
                $awardData['photo'] = $request->photo;
            }

            $award->update($awardData);

            return redirect()->back()->with('success', __('Award updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Award $award)
    {
        if (Auth::user()->can('delete-awards')) {
            // Check if award belongs to current company
            if (!in_array($award->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to delete this award'));
            }

            $award->delete();

            return redirect()->back()->with('success', __('Award deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Download certificate file.
     */
    public function downloadCertificate(Award $award)
    {
        if (Auth::user()->can('view-awards')) {
            // Check if award belongs to current company
            if (!in_array($award->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to access this certificate'));
            }

            if (!$award->certificate) {
                return redirect()->back()->with('error', __('Certificate file not found'));
            }

            $filePath = getStorageFilePath($award->certificate);

            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', __('Certificate file not found'));
            }

            return response()->download($filePath);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Download photo file.
     */
    public function downloadPhoto(Award $award)
    {
        if (Auth::user()->can('view-awards')) {
            // Check if award belongs to current company
            if (!in_array($award->created_by, getCompanyAndUsersId())) {
                return redirect()->back()->with('error', __('You do not have permission to access this photo'));
            }

            if (!$award->photo) {
                return redirect()->back()->with('error', __('Photo file not found'));
            }

            $filePath = getStorageFilePath($award->photo);

            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', __('Certificate file not found'));
            }

            return response()->download($filePath);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
