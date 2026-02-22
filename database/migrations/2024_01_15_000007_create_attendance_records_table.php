<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('attendance_policy_id')->nullable()->constrained('attendance_policies')->onDelete('set null');
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->decimal('break_hours', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('overtime_amount', 8, 2)->default(0);
            $table->boolean('is_late')->default(false);
            $table->boolean('is_early_departure')->default(false);
            $table->boolean('is_absent')->default(false);
            $table->boolean('is_holiday')->default(false);
            $table->boolean('is_weekend')->default(false);
            $table->enum('status', ['present', 'absent', 'half_day', 'on_leave', 'holiday'])->default('present');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint for employee and date
            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};