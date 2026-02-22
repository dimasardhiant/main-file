<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void{
    
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');

            // Monetary values (always decimal for accuracy)
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('component_earnings', 10, 2)->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2)->default(0);
            $table->decimal('overtime_amount', 10, 2)->default(0);
            $table->decimal('per_day_salary', 10, 2)->default(0);
            $table->decimal('unpaid_leave_deduction', 10, 2)->default(0);

            // Days (use decimal where half-days are possible)
            $table->integer('working_days')->default(0); // whole working days
            $table->decimal('present_days', 5, 2)->default(0); // allow 22.5 etc.
            $table->integer('full_present_days')->default(0); // whole days only
            $table->decimal('half_days', 5, 2)->default(0); // fractional (0.5, 1.5 etc.)
            $table->integer('holiday_days')->default(0); // whole days
            $table->decimal('paid_leave_days', 5, 2)->default(0); // could be 1.5
            $table->decimal('unpaid_leave_days', 5, 2)->default(0); // could be 2.5
            $table->integer('absent_days')->default(0); // whole days only

            // Overtime
            $table->decimal('overtime_hours', 5, 2)->default(0); // e.g., 2.75 hours

            // Breakdown JSONs
            $table->json('earnings_breakdown')->nullable();
            $table->json('deductions_breakdown')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint for employee per payroll run
            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
    }
};
