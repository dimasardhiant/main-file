<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_onboarding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('checklist_id')->constrained('onboarding_checklists')->onDelete('cascade');
            $table->date('start_date');
            $table->foreignId('buddy_employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['Pending', 'In Progress', 'Completed'])->default('Pending');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_onboarding');
    }
};