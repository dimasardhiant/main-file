<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Trip;
use App\Models\TripExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-trips')) {
            $query = Trip::with(['employee', 'approver'])->where(function ($q) {
                if (Auth::user()->can('manage-any-trips')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-trips')) {
                    $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                })
                    ->orWhere('purpose', 'like', '%' . $request->search . '%')
                    ->orWhere('destination', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id)) {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('start_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('end_date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $trips = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::with('employee')
                ->where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'employee_id' => $user->employee->employee_id ?? ''
                    ];
                });

            return Inertia::render('hr/trips/index', [
                'trips' => $trips,
                'employees' => $this->getFilteredEmployees(),
                'filters' => $request->all(['search', 'employee_id', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }



    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-trips') && !Auth::user()->can('manage-any-trips')) {
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
                    'employee_id' => $user->employee->employee_id ?? ''
                ];
            });
        return $employees;
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'purpose' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'expected_outcomes' => 'nullable|string',
            'documents' => 'nullable|string',
            'advance_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employee belongs to current company
        $user = User::where('id', $request->employee_id)
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();
        if (!$user) {
            return redirect()->back()->with('error', __('Invalid employee selected'));
        }

        $tripData = [
            'employee_id' => $request->employee_id,
            'purpose' => $request->purpose,
            'destination' => $request->destination,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'expected_outcomes' => $request->expected_outcomes,
            'status' => 'planned',
            'advance_amount' => $request->advance_amount,
            'advance_status' => $request->advance_amount > 0 ? 'requested' : null,
            'created_by' => creatorId(),
        ];

        // Handle document from media library
        if ($request->has('documents')) {
            $tripData['documents'] = $request->documents;
        }

        Trip::create($tripData);

        return redirect()->back()->with('success', __('Trip created successfully'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
    {
        // Check if trip belongs to current company
        if (!in_array($trip->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this trip');
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'purpose' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'expected_outcomes' => 'nullable|string',
            'status' => 'nullable|string|in:planned,ongoing,completed,cancelled',
            'documents' => 'nullable|string',
            'advance_amount' => 'nullable|numeric|min:0',
            'advance_status' => 'nullable|string|in:requested,approved,paid,reconciled',
            'reimbursement_status' => 'nullable|string|in:pending,approved,paid',
            'trip_report' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employee belongs to current company
        $user = User::where('id', $request->employee_id)
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Invalid employee selected');
        }

        $tripData = [
            'employee_id' => $request->employee_id,
            'purpose' => $request->purpose,
            'destination' => $request->destination,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'expected_outcomes' => $request->expected_outcomes,
            'advance_amount' => $request->advance_amount,
            'advance_status' => $request->advance_status,
            'reimbursement_status' => $request->reimbursement_status,
            'trip_report' => $request->trip_report,
        ];

        // Update status if provided and different from current
        if ($request->has('status') && $request->status !== $trip->status) {
            $tripData['status'] = $request->status;

            // If status is being set to ongoing, completed, or cancelled, set approved_by and approved_at
            if (in_array($request->status, ['ongoing', 'completed', 'cancelled']) && !$trip->approved_by) {
                $tripData['approved_by'] = auth()->id();
                $tripData['approved_at'] = now();
            }
        }

        // Handle document from media library
        if ($request->has('documents')) {
            $tripData['documents'] = $request->documents;
        }

        $trip->update($tripData);

        return redirect()->back()->with('success', __('Trip updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        // Check if trip belongs to current company
        if (!in_array($trip->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to delete this trip');
        }

        // Delete associated expenses
        foreach ($trip->expenses as $expense) {
            $expense->delete();
        }

        $trip->delete();

        return redirect()->back()->with('success', __('Trip deleted successfully'));
    }

    /**
     * Change the status of the trip.
     */
    public function changeStatus(Request $request, Trip $trip)
    {
        // Check if trip belongs to current company
        if (!in_array($trip->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this trip');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:planned,ongoing,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'status' => $request->status,
        ];

        // If status is being set to ongoing, completed, or cancelled, set approved_by and approved_at
        if (in_array($request->status, ['ongoing', 'completed', 'cancelled']) && !$trip->approved_by) {
            $updateData['approved_by'] = auth()->id();
            $updateData['approved_at'] = now();
        }

        $trip->update($updateData);

        return redirect()->back()->with('success', __('Trip status updated successfully'));
    }

    /**
     * Update the advance status of the trip.
     */
    public function updateAdvanceStatus(Request $request, Trip $trip)
    {
        // Check if trip belongs to current company
        if (!in_array($trip->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this trip');
        }

        $validator = Validator::make($request->all(), [
            'advance_status' => 'required|string|in:requested,approved,paid,reconciled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $trip->update([
            'advance_status' => $request->advance_status,
        ]);

        return redirect()->back()->with('success', __('Trip advance status updated successfully'));
    }

    /**
     * Update the reimbursement status of the trip.
     */
    public function updateReimbursementStatus(Request $request, Trip $trip)
    {
        // Check if trip belongs to current company
        if (!in_array($trip->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this trip');
        }

        $validator = Validator::make($request->all(), [
            'reimbursement_status' => 'required|string|in:pending,approved,paid',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $trip->update([
            'reimbursement_status' => $request->reimbursement_status,
        ]);

        return redirect()->back()->with('success', __('Trip reimbursement status updated successfully'));
    }

    /**
     * Download document file.
     */
    public function downloadDocument(Trip $trip)
    {
        // Check if trip belongs to current company
        if (!in_array($trip->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to access this document');
        }

        if (!$trip->documents) {
            return redirect()->back()->with('error', 'Document file not found');
        }

        $filePath = getStorageFilePath($trip->documents);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Document file not found');
        }

        return response()->download($filePath);
    }

    /**
     * Show the trip expenses.
     */
    public function showExpenses(Trip $trip)
    {
        // Check if trip belongs to current company
        if (!in_array($trip->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to view these expenses');
        }

        $expenses = $trip->expenses()->orderBy('expense_date', 'desc')->get();

        return Inertia::render('hr/trips/expenses', [
            'trip' => $trip->load('employee'),
            'expenses' => $expenses,
        ]);
    }

    /**
     * Store a new expense for the trip.
     */
    public function storeExpense(Request $request, Trip $trip)
    {
        // Check if trip belongs to current company
        if (!in_array($trip->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to add expenses to this trip');
        }

        $validator = Validator::make($request->all(), [
            'expense_type' => 'required|string|max:255',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'description' => 'nullable|string',
            'receipt' => 'nullable|string',
            'is_reimbursable' => 'nullable|boolean',
        ]);

        // Validate date range separately to avoid type conversion issues
        $expenseDate = $request->expense_date;
        if ($expenseDate < $trip->start_date->format('Y-m-d') || $expenseDate > $trip->end_date->format('Y-m-d')) {
            return redirect()->back()->withErrors(['expense_date' => 'The expense date must be between trip start and end dates.'])->withInput();
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $expenseData = [
            'trip_id' => $trip->id,
            'expense_type' => $request->expense_type,
            'expense_date' => $request->expense_date,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'description' => $request->description,
            'is_reimbursable' => $request->is_reimbursable ?? true,
            'status' => 'pending',
            'created_by' => creatorId(),
        ];

        // Handle receipt from media library
        if ($request->has('receipt')) {
            $expenseData['receipt'] = $request->receipt;
        }

        TripExpense::create($expenseData);

        // Update trip total expenses
        $totalExpenses = $trip->expenses()->sum('amount') + $request->amount;
        $trip->update([
            'total_expenses' => $totalExpenses,
            'reimbursement_status' => 'pending'
        ]);

        return redirect()->back()->with('success', __('Expense added successfully'));
    }

    /**
     * Update an expense for the trip.
     */
    public function updateExpense(Request $request, Trip $trip, TripExpense $expense)
    {
        // Check if expense belongs to this trip and company
        if ($expense->trip_id != $trip->id || !in_array($expense->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this expense');
        }

        $validator = Validator::make($request->all(), [
            'expense_type' => 'required|string|max:255',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'description' => 'nullable|string',
            'receipt' => 'nullable|string',
            'is_reimbursable' => 'nullable|boolean',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        // Validate date range separately to avoid type conversion issues
        $expenseDate = $request->expense_date;
        if ($expenseDate < $trip->start_date->format('Y-m-d') || $expenseDate > $trip->end_date->format('Y-m-d')) {
            return redirect()->back()->withErrors(['expense_date' => 'The expense date must be between trip start and end dates.'])->withInput();
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $expenseData = [
            'expense_type' => $request->expense_type,
            'expense_date' => $request->expense_date,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'description' => $request->description,
            'is_reimbursable' => $request->is_reimbursable ?? true,
            'status' => $request->status ?? $expense->status,
        ];

        // Handle receipt from media library
        if ($request->has('receipt')) {
            $expenseData['receipt'] = $request->receipt;
        }

        $oldAmount = $expense->amount;
        $expense->update($expenseData);

        // Update trip total expenses
        $totalExpenses = $trip->expenses()->sum('amount');
        $trip->update([
            'total_expenses' => $totalExpenses
        ]);

        return redirect()->back()->with('success', __('Expense updated successfully'));
    }

    /**
     * Delete an expense for the trip.
     */
    public function destroyExpense(Trip $trip, TripExpense $expense)
    {
        // Check if expense belongs to this trip and company
        if ($expense->trip_id != $trip->id || !in_array($expense->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to delete this expense');
        }

        // Delete receipt if exists
        if ($expense->receipt) {
            Storage::disk('public')->delete($expense->receipt);
        }

        $expense->delete();

        // Update trip total expenses
        $totalExpenses = $trip->expenses()->sum('amount');
        $trip->update([
            'total_expenses' => $totalExpenses
        ]);

        return redirect()->back()->with('success', __('Expense deleted successfully'));
    }

    /**
     * Download receipt file.
     */
    public function downloadReceipt(Trip $trip, TripExpense $expense)
    {
        // Check if expense belongs to this trip and company
        if ($expense->trip_id != $trip->id || !in_array($expense->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to access this receipt');
        }

        if (!$expense->receipt) {
            return redirect()->back()->with('error', 'Receipt file not found');
        }

        $filePath = getStorageFilePath($expense->receipt);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Receipt file not found');
        }

        return response()->download($filePath);
    }
}
