<?php

namespace App\Http\Controllers;

use App\Models\ContractRenewal;
use App\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ContractRenewalController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractRenewal::withPermissionCheck()->with(['contract.employee', 'requester', 'approver']);

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('renewal_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('contract.employee', function ($eq) use ($request) {
                        $eq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('contract_id') && !empty($request->contract_id) && $request->contract_id !== 'all') {
            $query->where('contract_id', $request->contract_id);
        }

        $query->orderBy('id', 'desc');
        $contractRenewals = $query->paginate($request->per_page ?? 10);

        $contracts = EmployeeContract::with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->whereNotNull('end_date')
            ->select('id', 'contract_number', 'employee_id', 'end_date')
            ->get();

        $employees = User::whereIn('created_by', getCompanyAndUsersId())
            ->whereIn('type', ['employee', 'manager','hr'])
            ->select('id', 'name')
            ->get();

        return Inertia::render('hr/contracts/contract-renewals/index', [
            'contractRenewals' => $contractRenewals,
            'contracts' => $contracts,
            'employees' => $employees,
            'filters' => $request->all(['search', 'status', 'contract_id', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:employee_contracts,id',
            'new_start_date' => 'required|date',
            'new_end_date' => 'required|date|after:new_start_date',
            'new_basic_salary' => 'required|numeric|min:0',
            'new_allowances' => 'nullable|array',
            'new_benefits' => 'nullable|array',
            'new_terms_conditions' => 'nullable|string',
            'changes_summary' => 'nullable|string',
            'reason' => 'nullable|string',
            'requested_by' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $contract = EmployeeContract::find($request->contract_id);
        
        // Generate renewal number
        $lastRenewal = ContractRenewal::where('contract_id', $request->contract_id)
            ->orderBy('id', 'desc')
            ->first();
        $nextNumber = $lastRenewal ? (intval(substr($lastRenewal->renewal_number, -2)) + 1) : 1;
        $renewalNumber = 'REN-' . str_pad(creatorId(), 3, '0', STR_PAD_LEFT) . '-' . str_pad($request->contract_id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

        ContractRenewal::create([
            'contract_id' => $request->contract_id,
            'renewal_number' => $renewalNumber,
            'current_end_date' => $contract->end_date,
            'new_start_date' => $request->new_start_date,
            'new_end_date' => $request->new_end_date,
            'new_basic_salary' => $request->new_basic_salary,
            'new_allowances' => $request->new_allowances,
            'new_benefits' => $request->new_benefits,
            'new_terms_conditions' => $request->new_terms_conditions,
            'changes_summary' => $request->changes_summary,
            'reason' => $request->reason,
            'requested_by' => $request->requested_by,
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Contract renewal created successfully'));
    }

    public function update(Request $request, ContractRenewal $contractRenewal)
    {
        if (!in_array($contractRenewal->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this renewal'));
        }

        if ($contractRenewal->status !== 'Pending') {
            return redirect()->back()->with('error', __('Cannot update renewal that is not pending'));
        }

        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:employee_contracts,id',
            'new_start_date' => 'required|date',
            'new_end_date' => 'required|date|after:new_start_date',
            'new_basic_salary' => 'required|numeric|min:0',
            'new_allowances' => 'nullable|array',
            'new_benefits' => 'nullable|array',
            'new_terms_conditions' => 'nullable|string',
            'changes_summary' => 'nullable|string',
            'reason' => 'nullable|string',
            'requested_by' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $contractRenewal->update([
            'new_start_date' => $request->new_start_date,
            'new_end_date' => $request->new_end_date,
            'new_basic_salary' => $request->new_basic_salary,
            'new_allowances' => $request->new_allowances,
            'new_benefits' => $request->new_benefits,
            'new_terms_conditions' => $request->new_terms_conditions,
            'changes_summary' => $request->changes_summary,
            'reason' => $request->reason,
            'requested_by' => $request->requested_by,
        ]);

        return redirect()->back()->with('success', __('Contract renewal updated successfully'));
    }

    public function destroy(ContractRenewal $contractRenewal)
    {
        if (!in_array($contractRenewal->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this renewal'));
        }

        if ($contractRenewal->status === 'Processed') {
            return redirect()->back()->with('error', __('Cannot delete processed renewal'));
        }

        $contractRenewal->delete();
        return redirect()->back()->with('success', __('Contract renewal deleted successfully'));
    }

    public function approve(Request $request, ContractRenewal $contractRenewal)
    {
        if (!in_array($contractRenewal->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to approve this renewal'));
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $contractRenewal->update([
            'status' => 'Approved',
            'approved_by' => creatorId(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        return redirect()->back()->with('success', __('Renewal approved successfully'));
    }

    public function reject(Request $request, ContractRenewal $contractRenewal)
    {
        if (!in_array($contractRenewal->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to reject this renewal'));
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $contractRenewal->update([
            'status' => 'Rejected',
            'approved_by' => creatorId(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        return redirect()->back()->with('success', __('Renewal rejected successfully'));
    }

    public function process(ContractRenewal $contractRenewal)
    {
        if (!in_array($contractRenewal->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to process this renewal'));
        }

        if ($contractRenewal->status !== 'Approved') {
            return redirect()->back()->with('error', __('Can only process approved renewals'));
        }

        // Update the original contract
        $contract = $contractRenewal->contract;
        $contract->update([
            'end_date' => $contractRenewal->new_end_date,
            'basic_salary' => $contractRenewal->new_basic_salary,
            'allowances' => $contractRenewal->new_allowances,
            'benefits' => $contractRenewal->new_benefits,
            'terms_conditions' => $contractRenewal->new_terms_conditions,
            'status' => 'Renewed',
        ]);

        $contractRenewal->update(['status' => 'Processed']);

        return redirect()->back()->with('success', __('Renewal processed and contract updated successfully'));
    }
}