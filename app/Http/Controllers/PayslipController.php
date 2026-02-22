<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\PayrollEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PayslipController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-payslips')) {
            $query = Payslip::with(['employee', 'payrollEntry.payrollRun', 'creator'])->where(function ($q) {
                if (Auth::user()->can('manage-any-payslips')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-payslips')) {
                    $q->orWhere('employee_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('payslip_number', 'like', '%' . $request->search . '%')
                        ->orWhereHas('employee', function ($subQ) use ($request) {
                            $subQ->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && !empty($request->employee_id) && $request->employee_id !== 'all') {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle status filter
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->where('pay_period_start', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->where('pay_period_end', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
            } else {
                $query->orderBy('pay_date', 'desc');
            }

            $payslips = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get(['id', 'name']);

            return Inertia::render('hr/payslips/index', [
                'payslips' => $payslips,
                'employees' => $employees,
                'filters' => $request->all(['search', 'employee_id', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'payroll_entry_ids' => 'required|array',
            'payroll_entry_ids.*' => 'exists:payroll_entries,id',
        ]);

        $generatedCount = 0;
        $errors = [];

        foreach ($validated['payroll_entry_ids'] as $entryId) {
            try {
                $payrollEntry = PayrollEntry::whereIn('created_by', getCompanyAndUsersId())
                    ->find($entryId);

                if (!$payrollEntry) {
                    continue;
                }

                // Check if payslip already exists
                $exists = Payslip::where('payroll_entry_id', $entryId)->exists();
                if ($exists) {
                    continue;
                }

                $payslipNumber = Payslip::generatePayslipNumber(
                    $payrollEntry->employee_id,
                    $payrollEntry->payrollRun->pay_date
                );

                $payslip = Payslip::create([
                    'payroll_entry_id' => $entryId,
                    'employee_id' => $payrollEntry->employee_id,
                    'payslip_number' => $payslipNumber,
                    'pay_period_start' => $payrollEntry->payrollRun->pay_period_start,
                    'pay_period_end' => $payrollEntry->payrollRun->pay_period_end,
                    'pay_date' => $payrollEntry->payrollRun->pay_date,
                    'status' => 'generated',
                    'created_by' => creatorId(),
                ]);

                // Generate PDF
                $payslip->generatePDF();
                $generatedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to generate payslip for entry ID {$entryId}: " . $e->getMessage();
            }
        }

        if ($generatedCount > 0) {
            $message = "Generated {$generatedCount} payslip(s) successfully.";
            if (!empty($errors)) {
                $message .= " Some errors occurred: " . implode(', ', $errors);
            }
            return redirect()->back()->with('success', __($message));
        } else {
            return redirect()->back()->with('error', __('No payslips were generated. :errors', ['errors' => implode(', ', $errors)]));
        }
    }

    public function download($payslipId)
    {
        $payslip = Payslip::where('id', $payslipId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if (!$payslip) {
            return redirect()->back()->with('error', __('Payslip not found.'));
        }

        if (!$payslip->file_path || !Storage::disk('public')->exists($payslip->file_path)) {
            // Generate PDF if not exists
            try {
                $payslip->generatePDF();
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to generate payslip PDF: :message', ['message' => $e->getMessage()]));
            }
        }

        $payslip->markAsDownloaded();

        return Storage::disk('public')->download($payslip->file_path, 'payslip-' . $payslip->payslip_number . '.pdf');
    }

    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'payroll_run_id' => 'required|exists:payroll_runs,id',
        ]);

        try {
            $payrollEntries = PayrollEntry::where('payroll_run_id', $validated['payroll_run_id'])
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get();

            $generatedCount = 0;

            foreach ($payrollEntries as $entry) {
                // Check if payslip already exists
                $exists = Payslip::where('payroll_entry_id', $entry->id)->exists();
                if ($exists) {
                    continue;
                }

                $payslipNumber = Payslip::generatePayslipNumber(
                    $entry->employee_id,
                    $entry->payrollRun->pay_date
                );

                $payslip = Payslip::create([
                    'payroll_entry_id' => $entry->id,
                    'employee_id' => $entry->employee_id,
                    'payslip_number' => $payslipNumber,
                    'pay_period_start' => $entry->payrollRun->pay_period_start,
                    'pay_period_end' => $entry->payrollRun->pay_period_end,
                    'pay_date' => $entry->payrollRun->pay_date,
                    'status' => 'generated',
                    'created_by' => creatorId(),
                ]);

                // Generate PDF
                $payslip->generatePDF();
                $generatedCount++;
            }

            return redirect()->back()->with('success', __('Generated :count payslips successfully.', ['count' => $generatedCount]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to generate payslips: :message', ['message' => $e->getMessage()]));
        }
    }
}
