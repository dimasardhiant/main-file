<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Payslip extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'payroll_entry_id',
        'employee_id',
        'payslip_number',
        'pay_period_start',
        'pay_period_end',
        'pay_date',
        'file_path',
        'status',
        'sent_at',
        'downloaded_at',
        'created_by',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'pay_date' => 'date',
        'sent_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    /**
     * Get the payroll entry.
     */
    public function payrollEntry()
    {
        return $this->belongsTo(PayrollEntry::class);
    }

    /**
     * Get the employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who created the payslip.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate payslip number.
     */
    public static function generatePayslipNumber($employeeId, $payDate)
    {
        $date = \Carbon\Carbon::parse($payDate);
        $prefix = 'PS-'.$date->format('Ym').'-';
        $employeeCode = str_pad($employeeId, 4, '0', STR_PAD_LEFT);

        return $prefix.$employeeCode;
    }

    /**
     * Generate PDF payslip.
     */
    public function generatePDF()
    {
        $payrollEntry = $this->payrollEntry()->with(['employee', 'payrollRun'])->first();

        if (! $payrollEntry) {
            throw new \Exception('Payroll entry not found');
        }

        $data = [
            'payslip' => $this,
            'payrollEntry' => $payrollEntry,
            'employee' => $payrollEntry->employee,
            'payrollRun' => $payrollEntry->payrollRun,
            'earnings' => $payrollEntry->earnings_breakdown ?? [],
            'deductions' => $payrollEntry->deductions_breakdown ?? [],
            'employeeData' => $payrollEntry->employee->employee,
            'companySettings' => settings(),
        ];

        $pdf = Pdf::loadView('payslips.template', $data);

        $fileName = 'payslip-'.$this->payslip_number.'.pdf';
        $filePath = 'payslips/'.$fileName;

        Storage::disk('public')->put($filePath, $pdf->output());

        $this->update(['file_path' => $filePath]);

        return $filePath;
    }

    /**
     * Get download URL.
     */
    public function getDownloadUrlAttribute()
    {
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return null;
    }

    /**
     * Mark as downloaded.
     */
    public function markAsDownloaded()
    {
        $this->update([
            'status' => 'downloaded',
            'downloaded_at' => now(),
        ]);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
