<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'biometric_emp_id',
        'employee_code',
        'phone',
        'date_of_birth',
        'gender',
        'branch_id',
        'department_id',
        'designation_id',
        'shift_id',
        'attendance_policy_id',
        'date_of_joining',
        'employment_type',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'postal_code',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
        'bank_name',
        'account_holder_name',
        'account_number',
        'bank_identifier_code',
        'bank_branch',
        'tax_payer_id',
        'employee_status',
        'created_by'
    ];

    /**
     * Get the branch that the employee belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the department that the employee belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the designation that the employee has.
     */
    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Get the shift that the employee belongs to.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the attendance policy that the employee has.
     */
    public function attendancePolicy()
    {
        return $this->belongsTo(AttendancePolicy::class);
    }

    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who created the employee.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employee's documents.
     */
    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class,'employee_id','user_id');
    }

    /**
     * Generate unique employee ID
     */
    public static function generateEmployeeId()
    {
        $lastEmployee = self::orderBy('id', 'desc')->first();
        $nextId = $lastEmployee ? $lastEmployee->id + 1 : 1;
        
        return 'EMP' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}