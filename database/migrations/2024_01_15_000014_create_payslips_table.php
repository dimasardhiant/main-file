<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_entry_id')->constrained('payroll_entries')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->string('payslip_number')->unique();
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->date('pay_date');
            $table->string('file_path')->nullable();
            $table->enum('status', ['generated', 'sent', 'downloaded'])->default('generated');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};