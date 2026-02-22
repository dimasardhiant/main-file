<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('employee_contracts')->onDelete('cascade');
            $table->string('renewal_number');
            $table->date('current_end_date');
            $table->date('new_start_date');
            $table->date('new_end_date');
            $table->decimal('new_basic_salary', 10, 2);
            $table->json('new_allowances')->nullable();
            $table->json('new_benefits')->nullable();
            $table->text('new_terms_conditions')->nullable();
            $table->text('changes_summary')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Processed'])->default('Pending');
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_renewals');
    }
};