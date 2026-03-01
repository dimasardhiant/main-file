<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Payslip;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

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

            // Handle payroll run filter
            if ($request->has('payroll_run_id') && !empty($request->payroll_run_id) && $request->payroll_run_id !== 'all') {
                $query->whereHas('payrollEntry', function ($q) use ($request) {
                    $q->where('payroll_run_id', $request->payroll_run_id);
                });
            }

            // Handle branch filter
            if ($request->has('branch') && !empty($request->branch) && $request->branch !== 'all') {
                $query->whereHas('employee.employee', function ($q) use ($request) {
                    $q->where('branch_id', $request->branch);
                });
            }

            // Handle department filter
            if ($request->has('department') && !empty($request->department) && $request->department !== 'all') {
                $query->whereHas('employee.employee', function ($q) use ($request) {
                    $q->where('department_id', $request->department);
                });
            }

            // Handle designation filter
            if ($request->has('designation') && !empty($request->designation) && $request->designation !== 'all') {
                $query->whereHas('employee.employee', function ($q) use ($request) {
                    $q->where('designation_id', $request->designation);
                });
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

            // Get branches, departments, designations for filters
            $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get(['id', 'name']);

            $departments = Department::with('branch')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get(['id', 'name', 'branch_id']);

            $designations = Designation::with('department')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get(['id', 'name', 'department_id']);

            // Get payroll runs for filter dropdown
            $payrollRuns = PayrollRun::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'completed')
                ->orderBy('pay_period_start', 'desc')
                ->get(['id', 'title', 'pay_period_start', 'pay_period_end']);

            return Inertia::render('hr/payslips/index', [
                'payslips' => $payslips,
                'employees' => $employees,
                'branches' => $branches,
                'departments' => $departments,
                'designations' => $designations,
                'payrollRuns' => $payrollRuns,
                'filters' => $request->all(['search', 'employee_id', 'status', 'date_from', 'date_to', 'branch', 'department', 'designation', 'payroll_run_id', 'sort_field', 'sort_direction', 'per_page']),
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

    /**
     * Export payslips to Excel.
     */
    public function exportExcel(Request $request)
    {
        // Build query with same filters as index
        $query = Payslip::with(['employee.employee.branch', 'employee.employee.department', 'employee.employee.designation', 'payrollEntry'])
            ->where(function ($q) {
                if (Auth::user()->can('manage-any-payslips')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-payslips')) {
                    $q->orWhere('employee_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        // Apply filters
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('payslip_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('employee', function ($subQ) use ($request) {
                        $subQ->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        if ($request->has('employee_id') && !empty($request->employee_id) && $request->employee_id !== 'all') {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('pay_period_start', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('pay_period_end', '<=', $request->date_to);
        }

        if ($request->has('branch') && !empty($request->branch) && $request->branch !== 'all') {
            $query->whereHas('employee.employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch);
            });
        }

        if ($request->has('department') && !empty($request->department) && $request->department !== 'all') {
            $query->whereHas('employee.employee', function ($q) use ($request) {
                $q->where('department_id', $request->department);
            });
        }

        if ($request->has('designation') && !empty($request->designation) && $request->designation !== 'all') {
            $query->whereHas('employee.employee', function ($q) use ($request) {
                $q->where('designation_id', $request->designation);
            });
        }

        // Handle payroll run filter
        if ($request->has('payroll_run_id') && !empty($request->payroll_run_id) && $request->payroll_run_id !== 'all') {
            $query->whereHas('payrollEntry', function ($q) use ($request) {
                $q->where('payroll_run_id', $request->payroll_run_id);
            });
        }

        $payslips = $query->orderBy('pay_date', 'desc')->get();

        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payslips');

        // Define column headers
        $headers = [
            'A' => 'No',
            'B' => 'Employee Name',
            'C' => 'Payslip Number',
            'D' => 'Pay Period',
            'E' => 'Branch',
            'F' => 'Department',
            'G' => 'Designation',
            'H' => 'Basic Salary',
            'I' => 'Monthly Salary - IDR',
            'J' => 'THR & PKWT',
            'K' => 'Bonus',
            'L' => 'EE BPJS Working Social Security (JHT,JKK,JKM)',
            'M' => 'EE BPJS Healthcare Scheme',
            'N' => 'EE Pension Scheme',
            'O' => 'EE Regular Personal Income Tax',
            'P' => 'EE Irregular Income Tax',
            'Q' => 'Expenses',
            'R' => 'ER Regular Personal Income Tax',
            'S' => 'ER Irregular Income Tax',
            'T' => 'ER BPJS Working Social Security (JHT,JKK,JKM)',
            'U' => 'ER BPJS Healthcare Scheme',
            'V' => 'ER Pension Scheme',
            'W' => 'Net Pay-IDR',
            'X' => 'Total Statutory and Tax',
            'Y' => 'Total Employer Cost',
        ];

        // Row 1: Category headers
        $sheet->setCellValue('J1', 'Earnings');
        $sheet->mergeCells('J1:K1');
        $sheet->setCellValue('L1', 'EE (Employee Deductions)');
        $sheet->mergeCells('L1:Q1');
        $sheet->setCellValue('R1', 'ER (Employer Contributions)');
        $sheet->mergeCells('R1:V1');

        // Row 2: Column names
        foreach ($headers as $col => $headerText) {
            $sheet->setCellValue($col . '2', $headerText);
        }

        // Style category header row (Row 1)
        $sheet->getStyle('A1:Y1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Style column header row (Row 2) - green
        $sheet->getStyle('A2:Y2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // EE columns yellow
        $sheet->getStyle('L2:Q2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
        ]);

        // ER columns orange
        $sheet->getStyle('R2:V2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
        ]);

        // Result columns pink
        $sheet->getStyle('W2:Y2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF99CC']],
        ]);

        // Set column widths
        $columnWidths = [
            'A' => 5, 'B' => 25, 'C' => 20, 'D' => 22, 'E' => 18, 'F' => 18, 'G' => 18,
            'H' => 18, 'I' => 18, 'J' => 15, 'K' => 15,
            'L' => 20, 'M' => 18, 'N' => 15, 'O' => 18, 'P' => 18, 'Q' => 15,
            'R' => 18, 'S' => 18, 'T' => 20, 'U' => 18, 'V' => 15,
            'W' => 18, 'X' => 18, 'Y' => 20,
        ];
        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(40);

        // Populate data
        $dataRow = 3;
        $no = 1;

        foreach ($payslips as $payslip) {
            $entry = $payslip->payrollEntry;
            $employee = $payslip->employee;
            $employeeRecord = $employee?->employee;

            $basicSalary = $entry ? (float) $entry->basic_salary : 0;
            $netPay = $entry ? (float) $entry->net_pay : 0;
            $earningsBreakdown = $entry ? ($entry->earnings_breakdown ?? []) : [];
            $deductionsBreakdown = $entry ? ($entry->deductions_breakdown ?? []) : [];

            // Pay period
            $payPeriod = '';
            if ($payslip->pay_period_start && $payslip->pay_period_end) {
                $payPeriod = $payslip->pay_period_start->format('d/m/Y') . ' - ' . $payslip->pay_period_end->format('d/m/Y');
            }

            // Initialize column values
            $eeValues = [
                'bpjs_social_security' => 0, 'bpjs_healthcare' => 0, 'pension' => 0,
                'regular_income_tax' => 0, 'irregular_income_tax' => 0, 'expenses' => 0,
            ];

            $erValues = [
                'regular_income_tax' => 0, 'irregular_income_tax' => 0,
                'bpjs_social_security' => 0, 'bpjs_healthcare' => 0, 'pension' => 0,
            ];

            $earningValues = ['thr_pkwt' => 0, 'bonus' => 0];

            // Map deductions and ER contributions from the combined array
            foreach ($deductionsBreakdown as $name => $amount) {
                $lowerName = strtolower($name);
                $amount = (float) $amount;

                // Check if this is an ER Contribution
                if (strpos($name, 'ER_') === 0) {
                    if (strpos($lowerName, 'bpjs_kesehatan') !== false) {
                        $erValues['bpjs_healthcare'] += $amount;
                    } elseif (strpos($lowerName, 'bpjs_jht') !== false || strpos($lowerName, 'bpjs_jkk') !== false || strpos($lowerName, 'bpjs_jkm') !== false) {
                        $erValues['bpjs_social_security'] += $amount;
                    } elseif (strpos($lowerName, 'bpjs_jp') !== false) {
                        $erValues['pension'] += $amount;
                    } elseif (strpos($lowerName, 'tax') !== false || strpos($lowerName, 'pph') !== false) {
                        if (strpos($lowerName, 'irregular') !== false) {
                            $erValues['irregular_income_tax'] += $amount;
                        } else {
                            $erValues['regular_income_tax'] += $amount;
                        }
                    }
                    continue; // Skip the rest of the EE logic for ER items
                }

                if (strpos($lowerName, 'bpjs') !== false && (strpos($lowerName, 'jht') !== false || strpos($lowerName, 'jkk') !== false || strpos($lowerName, 'jkm') !== false || strpos($lowerName, 'social') !== false || strpos($lowerName, 'ketenagakerjaan') !== false || strpos($lowerName, 'working') !== false)) {
                    $eeValues['bpjs_social_security'] += $amount;
                } elseif (strpos($lowerName, 'bpjs') !== false && (strpos($lowerName, 'health') !== false || strpos($lowerName, 'kesehatan') !== false || strpos($lowerName, 'healthcare') !== false)) {
                    $eeValues['bpjs_healthcare'] += $amount;
                } elseif (strpos($lowerName, 'pension') !== false || strpos($lowerName, 'pensiun') !== false || strpos($lowerName, 'jp') !== false) {
                    $eeValues['pension'] += $amount;
                } elseif (strpos($lowerName, 'tax') !== false || strpos($lowerName, 'pph') !== false || strpos($lowerName, 'pajak') !== false) {
                    if (strpos($lowerName, 'irregular') !== false || strpos($lowerName, 'tidak teratur') !== false) {
                        $eeValues['irregular_income_tax'] += $amount;
                    } else {
                        $eeValues['regular_income_tax'] += $amount;
                    }
                } elseif (strpos($lowerName, 'expense') !== false || strpos($lowerName, 'biaya') !== false) {
                    $eeValues['expenses'] += $amount;
                } else {
                    $eeValues['expenses'] += $amount;
                }
            }

            // Map earnings
            foreach ($earningsBreakdown as $name => $amount) {
                $lowerName = strtolower($name);
                $amount = (float) $amount;

                if ($lowerName === 'basic salary') continue;

                if (strpos($lowerName, 'thr') !== false || strpos($lowerName, 'pkwt') !== false) {
                    $earningValues['thr_pkwt'] += $amount;
                } elseif (strpos($lowerName, 'bonus') !== false || strpos($lowerName, 'insentif') !== false || strpos($lowerName, 'incentive') !== false) {
                    $earningValues['bonus'] += $amount;
                }
            }

            // Calculate totals
            $totalEeDeductions = array_sum($eeValues);
            $totalErContributions = array_sum($erValues);
            $totalStatutoryAndTax = $totalEeDeductions + $totalErContributions;
            $totalEmployerCost = $netPay + $totalStatutoryAndTax;

            // Write row data
            $sheet->setCellValue('A' . $dataRow, $no);
            $sheet->setCellValue('B' . $dataRow, $employee?->name ?? '-');
            $sheet->setCellValue('C' . $dataRow, $payslip->payslip_number ?? '-');
            $sheet->setCellValue('D' . $dataRow, $payPeriod);
            $sheet->setCellValue('E' . $dataRow, $employeeRecord?->branch?->name ?? '-');
            $sheet->setCellValue('F' . $dataRow, $employeeRecord?->department?->name ?? '-');
            $sheet->setCellValue('G' . $dataRow, $employeeRecord?->designation?->name ?? '-');
            $sheet->setCellValue('H' . $dataRow, $basicSalary);
            $sheet->setCellValue('I' . $dataRow, $basicSalary);
            $sheet->setCellValue('J' . $dataRow, $earningValues['thr_pkwt'] ?: '-');
            $sheet->setCellValue('K' . $dataRow, $earningValues['bonus'] ?: '-');
            $sheet->setCellValue('L' . $dataRow, $eeValues['bpjs_social_security'] ?: '-');
            $sheet->setCellValue('M' . $dataRow, $eeValues['bpjs_healthcare'] ?: '-');
            $sheet->setCellValue('N' . $dataRow, $eeValues['pension'] ?: '-');
            $sheet->setCellValue('O' . $dataRow, $eeValues['regular_income_tax'] ?: '-');
            $sheet->setCellValue('P' . $dataRow, $eeValues['irregular_income_tax'] ?: '-');
            $sheet->setCellValue('Q' . $dataRow, $eeValues['expenses'] ?: '-');
            $sheet->setCellValue('R' . $dataRow, $erValues['regular_income_tax'] ?: '-');
            $sheet->setCellValue('S' . $dataRow, $erValues['irregular_income_tax'] ?: '-');
            $sheet->setCellValue('T' . $dataRow, $erValues['bpjs_social_security'] ?: '-');
            $sheet->setCellValue('U' . $dataRow, $erValues['bpjs_healthcare'] ?: '-');
            $sheet->setCellValue('V' . $dataRow, $erValues['pension'] ?: '-');
            $sheet->setCellValue('W' . $dataRow, $netPay);
            $sheet->setCellValue('X' . $dataRow, $totalStatutoryAndTax);
            $sheet->setCellValue('Y' . $dataRow, $totalEmployerCost);

            // Number format
            foreach (range('H', 'Y') as $col) {
                $cell = $sheet->getCell($col . $dataRow);
                if (is_numeric($cell->getValue())) {
                    $sheet->getStyle($col . $dataRow)->getNumberFormat()->setFormatCode('#,##0');
                }
            }

            // Borders
            $sheet->getStyle('A' . $dataRow . ':Y' . $dataRow)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $no++;
            $dataRow++;
        }

        // Auto-filter
        if ($dataRow > 3) {
            $sheet->setAutoFilter('A2:Y' . ($dataRow - 1));
        }

        // Create response
        $fileName = 'Payslips_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'payslip_export_');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export payslips to PDF.
     */
    public function exportPdf(Request $request)
    {
        // Build query with same filters as index
        $query = Payslip::with(['employee.employee.branch', 'employee.employee.department', 'employee.employee.designation', 'payrollEntry'])
            ->where(function ($q) {
                if (Auth::user()->can('manage-any-payslips')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-payslips')) {
                    $q->orWhere('employee_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('payslip_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('employee', function ($subQ) use ($request) {
                        $subQ->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }
        if ($request->has('employee_id') && !empty($request->employee_id) && $request->employee_id !== 'all') {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('pay_period_start', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('pay_period_end', '<=', $request->date_to);
        }
        if ($request->has('branch') && !empty($request->branch) && $request->branch !== 'all') {
            $query->whereHas('employee.employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch);
            });
        }
        if ($request->has('department') && !empty($request->department) && $request->department !== 'all') {
            $query->whereHas('employee.employee', function ($q) use ($request) {
                $q->where('department_id', $request->department);
            });
        }
        if ($request->has('designation') && !empty($request->designation) && $request->designation !== 'all') {
            $query->whereHas('employee.employee', function ($q) use ($request) {
                $q->where('designation_id', $request->designation);
            });
        }

        // Handle payroll run filter
        if ($request->has('payroll_run_id') && !empty($request->payroll_run_id) && $request->payroll_run_id !== 'all') {
            $query->whereHas('payrollEntry', function ($q) use ($request) {
                $q->where('payroll_run_id', $request->payroll_run_id);
            });
        }

        $payslips = $query->orderBy('pay_date', 'desc')->get();

        // Build data for PDF
        $rows = [];
        $no = 1;

        foreach ($payslips as $payslip) {
            $entry = $payslip->payrollEntry;
            $employee = $payslip->employee;
            $employeeRecord = $employee?->employee;

            $basicSalary = $entry ? (float) $entry->basic_salary : 0;
            $netPay = $entry ? (float) $entry->net_pay : 0;
            $earningsBreakdown = $entry ? ($entry->earnings_breakdown ?? []) : [];
            $deductionsBreakdown = $entry ? ($entry->deductions_breakdown ?? []) : [];

            $payPeriod = '';
            if ($payslip->pay_period_start && $payslip->pay_period_end) {
                $payPeriod = $payslip->pay_period_start->format('d/m/Y') . ' - ' . $payslip->pay_period_end->format('d/m/Y');
            }

            $eeValues = [
                'bpjs_social_security' => 0, 'bpjs_healthcare' => 0, 'pension' => 0,
                'regular_income_tax' => 0, 'irregular_income_tax' => 0, 'expenses' => 0,
            ];
            $erValues = [
                'regular_income_tax' => 0, 'irregular_income_tax' => 0,
                'bpjs_social_security' => 0, 'bpjs_healthcare' => 0, 'pension' => 0,
            ];
            $earningValues = ['thr_pkwt' => 0, 'bonus' => 0];

            foreach ($deductionsBreakdown as $name => $amount) {
                $lowerName = strtolower($name);
                $amount = (float) $amount;

                // Check if this is an ER Contribution
                if (strpos($name, 'ER_') === 0) {
                    if (strpos($lowerName, 'bpjs_kesehatan') !== false) {
                        $erValues['bpjs_healthcare'] += $amount;
                    } elseif (strpos($lowerName, 'bpjs_jht') !== false || strpos($lowerName, 'bpjs_jkk') !== false || strpos($lowerName, 'bpjs_jkm') !== false) {
                        $erValues['bpjs_social_security'] += $amount;
                    } elseif (strpos($lowerName, 'bpjs_jp') !== false) {
                        $erValues['pension'] += $amount;
                    } elseif (strpos($lowerName, 'tax') !== false || strpos($lowerName, 'pph') !== false) {
                        if (strpos($lowerName, 'irregular') !== false) {
                            $erValues['irregular_income_tax'] += $amount;
                        } else {
                            $erValues['regular_income_tax'] += $amount;
                        }
                    }
                    continue; // Skip the rest of the EE logic for ER items
                }

                if (strpos($lowerName, 'bpjs') !== false && (strpos($lowerName, 'jht') !== false || strpos($lowerName, 'jkk') !== false || strpos($lowerName, 'jkm') !== false || strpos($lowerName, 'social') !== false || strpos($lowerName, 'ketenagakerjaan') !== false || strpos($lowerName, 'working') !== false)) {
                    $eeValues['bpjs_social_security'] += $amount;
                } elseif (strpos($lowerName, 'bpjs') !== false && (strpos($lowerName, 'health') !== false || strpos($lowerName, 'kesehatan') !== false || strpos($lowerName, 'healthcare') !== false)) {
                    $eeValues['bpjs_healthcare'] += $amount;
                } elseif (strpos($lowerName, 'pension') !== false || strpos($lowerName, 'pensiun') !== false || strpos($lowerName, 'jp') !== false) {
                    $eeValues['pension'] += $amount;
                } elseif (strpos($lowerName, 'tax') !== false || strpos($lowerName, 'pph') !== false || strpos($lowerName, 'pajak') !== false) {
                    if (strpos($lowerName, 'irregular') !== false || strpos($lowerName, 'tidak teratur') !== false) {
                        $eeValues['irregular_income_tax'] += $amount;
                    } else {
                        $eeValues['regular_income_tax'] += $amount;
                    }
                } elseif (strpos($lowerName, 'expense') !== false || strpos($lowerName, 'biaya') !== false) {
                    $eeValues['expenses'] += $amount;
                } else {
                    $eeValues['expenses'] += $amount;
                }
            }

            foreach ($earningsBreakdown as $name => $amount) {
                $lowerName = strtolower($name);
                $amount = (float) $amount;
                if ($lowerName === 'basic salary') continue;
                if (strpos($lowerName, 'thr') !== false || strpos($lowerName, 'pkwt') !== false) {
                    $earningValues['thr_pkwt'] += $amount;
                } elseif (strpos($lowerName, 'bonus') !== false || strpos($lowerName, 'insentif') !== false || strpos($lowerName, 'incentive') !== false) {
                    $earningValues['bonus'] += $amount;
                }
            }

            $totalEeDeductions = array_sum($eeValues);
            $totalErContributions = array_sum($erValues);
            $totalStatutoryAndTax = $totalEeDeductions + $totalErContributions;
            $totalEmployerCost = $netPay + $totalStatutoryAndTax;

            $rows[] = [
                'no' => $no,
                'employee_name' => $employee?->name ?? '-',
                'payslip_number' => $payslip->payslip_number ?? '-',
                'pay_period' => $payPeriod,
                'branch' => $employeeRecord?->branch?->name ?? '-',
                'department' => $employeeRecord?->department?->name ?? '-',
                'designation' => $employeeRecord?->designation?->name ?? '-',
                'basic_salary' => $basicSalary,
                'monthly_salary' => $basicSalary,
                'thr_pkwt' => $earningValues['thr_pkwt'],
                'bonus' => $earningValues['bonus'],
                'ee_bpjs_social' => $eeValues['bpjs_social_security'],
                'ee_bpjs_health' => $eeValues['bpjs_healthcare'],
                'ee_pension' => $eeValues['pension'],
                'ee_regular_tax' => $eeValues['regular_income_tax'],
                'ee_irregular_tax' => $eeValues['irregular_income_tax'],
                'ee_expenses' => $eeValues['expenses'],
                'er_regular_tax' => $erValues['regular_income_tax'],
                'er_irregular_tax' => $erValues['irregular_income_tax'],
                'er_bpjs_social' => $erValues['bpjs_social_security'],
                'er_bpjs_health' => $erValues['bpjs_healthcare'],
                'er_pension' => $erValues['pension'],
                'net_pay' => $netPay,
                'total_statutory_tax' => $totalStatutoryAndTax,
                'total_employer_cost' => $totalEmployerCost,
            ];

            $no++;
        }

        $companySettings = settings();

        $html = view('payslips.export-pdf', [
            'rows' => $rows,
            'companyName' => $companySettings['company_name'] ?? 'Company',
            'exportDate' => now()->format('d/m/Y H:i'),
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a3', 'landscape');

        $fileName = 'Payslips_' . date('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }
}
