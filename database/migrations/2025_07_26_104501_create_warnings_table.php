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
        Schema::create('warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('warning_by')->constrained('users')->onDelete('cascade');
            $table->string('warning_type'); // attendance, performance, conduct, etc.
            $table->string('subject');
            $table->string('severity'); // verbal, written, final
            $table->date('warning_date');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, issued, acknowledged, expired
            $table->string('documents')->nullable(); // For storing document paths
            $table->date('acknowledgment_date')->nullable();
            $table->text('employee_response')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('has_improvement_plan')->default(false);
            $table->text('improvement_plan_goals')->nullable();
            $table->date('improvement_plan_start_date')->nullable();
            $table->date('improvement_plan_end_date')->nullable();
            $table->text('improvement_plan_progress')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warnings');
    }
};