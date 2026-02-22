<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->string('purpose');
            $table->string('destination');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->text('expected_outcomes')->nullable();
            $table->string('status')->default('planned'); // planned, ongoing, completed, cancelled
            $table->string('documents')->nullable(); // For storing document paths
            $table->decimal('advance_amount', 15, 2)->nullable();
            $table->string('advance_status')->nullable(); // requested, approved, paid, reconciled
            $table->decimal('total_expenses', 15, 2)->nullable();
            $table->string('reimbursement_status')->nullable(); // pending, approved, paid
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('trip_report')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create trip expenses table
        Schema::create('trip_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->string('expense_type'); // transportation, accommodation, meals, etc.
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->text('description')->nullable();
            $table->string('receipt')->nullable(); // For storing receipt file path
            $table->boolean('is_reimbursable')->default(true);
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_expenses');
        Schema::dropIfExists('trips');
    }
};