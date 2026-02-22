<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payrollEntry->employee->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }

        .payslip-title {
            font-size: 16px;
            color: #6c757d;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }

        .table th {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .employee-table th {
            background-color: #007bff;
            color: white;
        }

        .attendance-table th {
            background-color: #28a745;
            color: white;
        }

        .salary-table th {
            background-color: #17a2b8;
            color: white;
        }

        .earnings-header {
            background-color: #d4edda;
            color: #155724;
            font-weight: bold;
        }

        .deductions-header {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }

        .total-row {
            background-color: #f1f3f4;
            font-weight: bold;
        }

        .net-salary-row {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="company-name">                
                {{ isset($companySettings['titleText']) ? $companySettings['titleText'] : config('app.name', 'HRMGo SaaS') }}
            </div>
            <div class="payslip-title">Salary Slip for {{ $payrollEntry->payrollRun->pay_period_start->format('F Y') }}
            </div>
        </div>

        <!-- Employee Information -->
        <table class="table employee-table">
            <thead>
                <tr>
                    <th colspan="4">Employee Information</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Employee Name:</strong></td>
                    <td>{{ $payrollEntry->employee->name }}</td>
                    <td><strong>Employee ID:</strong></td>
                    <td>{{ $employeeData->employee_id ?? $payrollEntry->employee->id }}</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>{{ $payrollEntry->employee->email }}</td>
                    <td><strong>Pay Period:</strong></td>
                    <td>{{ $payrollEntry->payrollRun->pay_period_start->format('d M Y') }} -
                        {{ $payrollEntry->payrollRun->pay_period_end->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Basic Salary:</strong></td>
                    <td>{{ formatCurrency($payrollEntry->basic_salary) }}</td>
                    <td><strong>Generated On:</strong></td>
                    <td>{{ now()->format('d M Y') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Attendance Summary -->
        @if ($payrollEntry->working_days > 0)
            <table class="table attendance-table">
                <thead>
                    <tr>
                        <th colspan="6">Attendance Summary</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Working Days:</strong> {{ $payrollEntry->working_days }}</td>
                        <td><strong>Present:</strong> {{ $payrollEntry->present_days }}</td>
                        <td><strong>Paid Leave:</strong> {{ $payrollEntry->paid_leave_days }}</td>
                        <td><strong>Unpaid Leave:</strong> {{ $payrollEntry->unpaid_leave_days }}<br>
                            <small style="color: #6c757d; font-size: 10px;">(Unpaid Leaves + Half Days + Absent)</small>
                        </td>
                        <td><strong>Half Days:</strong> {{ $payrollEntry->half_days }}</td>
                        <td><strong>Absent:</strong> {{ $payrollEntry->absent_days }}</td>
                    </tr>
                    <tr>
                        <td colspan="6"><strong>Overtime Hours:</strong>
                            {{ number_format($payrollEntry->overtime_hours, 1) }}h</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- Deduction Calculation Details -->
        @php
            $perDaySalary = $payrollEntry->per_day_salary ?? 0;
            $unpaidLeaveDeduction = $payrollEntry->unpaid_leave_deduction ?? 0;
        @endphp

        @if ($unpaidLeaveDeduction > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th colspan="4" style="background-color: #ffc107; color: #212529;">Deduction Calculation
                            Details</th>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <th>Days/Details</th>
                        <th>Rate</th>
                        <th class="amount">Deduction Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background-color: #e9ecef;">
                        <td><strong>Per Day Salary</strong></td>
                        <td>Basic Salary / Working Days</td>
                        <td class="amount">{{ formatCurrency($payrollEntry->basic_salary) }} /
                            {{ $payrollEntry->working_days }}</td>
                        <td class="amount"><strong>{{ formatCurrency($perDaySalary) }}</strong></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Total Unpaid Leave Deduction</strong></td>
                        <td><strong>Absent + Half Days + Unpaid Leave</strong></td>
                        <td><strong>Total Deduction</strong></td>
                        <td class="amount"><strong>{{ formatCurrency($unpaidLeaveDeduction) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- Salary Details -->
        <table class="table salary-table">
            <thead>
                <tr>
                    <th colspan="4">Salary Details</th>
                </tr>
                <tr>
                    <th class="earnings-header">Earnings</th>
                    <th class="earnings-header amount">Amount</th>
                    <th class="deductions-header">Deductions</th>
                    <th class="deductions-header amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $earnings = $payrollEntry->earnings_breakdown ?? [];
                    $deductions = $payrollEntry->deductions_breakdown ?? [];

                    // Add overtime to earnings if exists
                    if ($payrollEntry->overtime_amount > 0) {
                        $earnings['Overtime Amount'] = $payrollEntry->overtime_amount;
                    }

                    // Add unpaid leave deduction if exists
                    if ($payrollEntry->unpaid_leave_deduction > 0) {
                        $deductions['Unpaid Leave Deduction'] = $payrollEntry->unpaid_leave_deduction;
                    }

                    $maxRows = max(count($earnings), count($deductions), 1);
                    $earningsKeys = array_keys($earnings);
                    $deductionsKeys = array_keys($deductions);

                    $totalEarnings = $payrollEntry->total_earnings + $payrollEntry->overtime_amount;
                    $totalDeductions = $payrollEntry->total_deductions + $payrollEntry->unpaid_leave_deduction;
                @endphp

                @for ($i = 0; $i < $maxRows; $i++)
                    <tr>
                        <td>{{ $earningsKeys[$i] ?? '' }}</td>
                        <td class="amount">
                            {{ isset($earningsKeys[$i]) ? formatCurrency($earnings[$earningsKeys[$i]]) : '' }}</td>
                        <td>{{ $deductionsKeys[$i] ?? '' }}</td>
                        <td class="amount">
                            {{ isset($deductionsKeys[$i]) ? formatCurrency($deductions[$deductionsKeys[$i]]) : '' }}
                        </td>
                    </tr>
                @endfor

                <tr class="total-row">
                    <td><strong>Total Earnings</strong></td>
                    <td class="amount"><strong>{{ formatCurrency($totalEarnings) }}</strong></td>
                    <td><strong>Total Deductions</strong></td>
                    <td class="amount"><strong>{{ formatCurrency($totalDeductions) }}</strong></td>
                </tr>

                <tr class="net-salary-row">
                    <td colspan="3"><strong>NET SALARY (Take Home)</strong></td>
                    <td class="amount"><strong>{{ formatCurrency($payrollEntry->net_pay) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p><strong>Note:</strong> This is a computer-generated payslip and does not require a physical signature.
            </p>
            <p>Generated on {{ now()->format('d M Y \\a\\t H:i:s') }} | {{ config('app.name', 'HRMGo SaaS') }}</p>
        </div>
    </div>
</body>

</html>
