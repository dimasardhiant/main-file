<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslips Export</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { font-size: 16px; margin-bottom: 3px; }
        .header p { font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 3px 4px; text-align: center; }
        .cat-header { background-color: #4472C4; color: #fff; font-size: 8px; font-weight: bold; }
        .col-header { font-weight: bold; font-size: 6.5px; background-color: #92D050; }
        .ee-header { background-color: #FFFF00 !important; }
        .er-header { background-color: #FFC000 !important; }
        .result-header { background-color: #FF99CC !important; }
        td { font-size: 7px; }
        td.text-left { text-align: left; }
        td.text-right { text-align: right; }
        .footer { margin-top: 10px; text-align: right; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyName }}</h1>
        <p>Payslips Report - Exported: {{ $exportDate }}</p>
    </div>

    <table>
        {{-- Category Header Row --}}
        <tr>
            <th class="cat-header" colspan="7"></th>
            <th class="cat-header" colspan="2"></th>
            <th class="cat-header" colspan="2">Earnings</th>
            <th class="cat-header" colspan="6">EE (Employee Deductions)</th>
            <th class="cat-header" colspan="5">ER (Employer Contributions)</th>
            <th class="cat-header" colspan="3"></th>
        </tr>
        {{-- Column Header Row --}}
        <tr>
            <th class="col-header">No</th>
            <th class="col-header">Employee</th>
            <th class="col-header">Payslip #</th>
            <th class="col-header">Pay Period</th>
            <th class="col-header">Branch</th>
            <th class="col-header">Dept</th>
            <th class="col-header">Position</th>
            <th class="col-header">Basic Salary</th>
            <th class="col-header">Monthly Salary</th>
            <th class="col-header">THR & PKWT</th>
            <th class="col-header">Bonus</th>
            <th class="col-header ee-header">BPJS Social Security</th>
            <th class="col-header ee-header">BPJS Healthcare</th>
            <th class="col-header ee-header">Pension</th>
            <th class="col-header ee-header">Regular Tax</th>
            <th class="col-header ee-header">Irregular Tax</th>
            <th class="col-header ee-header">Expenses</th>
            <th class="col-header er-header">Regular Tax</th>
            <th class="col-header er-header">Irregular Tax</th>
            <th class="col-header er-header">BPJS Social Security</th>
            <th class="col-header er-header">BPJS Healthcare</th>
            <th class="col-header er-header">Pension</th>
            <th class="col-header result-header">Net Pay</th>
            <th class="col-header result-header">Total Statutory & Tax</th>
            <th class="col-header result-header">Total Employer Cost</th>
        </tr>
        {{-- Data Rows --}}
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['no'] }}</td>
            <td class="text-left">{{ $row['employee_name'] }}</td>
            <td>{{ $row['payslip_number'] }}</td>
            <td>{{ $row['pay_period'] }}</td>
            <td>{{ $row['branch'] }}</td>
            <td>{{ $row['department'] }}</td>
            <td>{{ $row['designation'] }}</td>
            <td class="text-right">{{ number_format($row['basic_salary'], 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($row['monthly_salary'], 0, ',', '.') }}</td>
            <td class="text-right">{{ $row['thr_pkwt'] ? number_format($row['thr_pkwt'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['bonus'] ? number_format($row['bonus'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['ee_bpjs_social'] ? number_format($row['ee_bpjs_social'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['ee_bpjs_health'] ? number_format($row['ee_bpjs_health'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['ee_pension'] ? number_format($row['ee_pension'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['ee_regular_tax'] ? number_format($row['ee_regular_tax'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['ee_irregular_tax'] ? number_format($row['ee_irregular_tax'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['ee_expenses'] ? number_format($row['ee_expenses'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['er_regular_tax'] ? number_format($row['er_regular_tax'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['er_irregular_tax'] ? number_format($row['er_irregular_tax'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['er_bpjs_social'] ? number_format($row['er_bpjs_social'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['er_bpjs_health'] ? number_format($row['er_bpjs_health'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $row['er_pension'] ? number_format($row['er_pension'], 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ number_format($row['net_pay'], 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($row['total_statutory_tax'], 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($row['total_employer_cost'], 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>

    <div class="footer">
        <p>Generated on {{ $exportDate }}</p>
    </div>
</body>
</html>
