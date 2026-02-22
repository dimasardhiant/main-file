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
        Schema::create('resignations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->date('resignation_date');
            $table->date('last_working_day');
            $table->string('notice_period')->nullable();
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, completed
            $table->string('documents')->nullable(); // For storing document paths
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('exit_feedback')->nullable();
            $table->boolean('exit_interview_conducted')->default(false);
            $table->date('exit_interview_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resignations');
    }
};