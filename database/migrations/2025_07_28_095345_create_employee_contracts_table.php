<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('contract_type_id')->constrained('contract_types')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('basic_salary', 10, 2);
            $table->json('allowances')->nullable();
            $table->json('benefits')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->enum('status', ['Draft', 'Pending Approval', 'Active', 'Expired', 'Terminated', 'Renewed'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};