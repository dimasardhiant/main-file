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
        Schema::create('employee_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('training_program_id')->constrained('training_programs')->onDelete('cascade');
            $table->string('status')->default('assigned'); // assigned, in_progress, completed, failed
            $table->date('assigned_date');
            $table->date('completion_date')->nullable();
            $table->string('certification')->nullable(); // Certification details or path to certificate file
            $table->decimal('score', 5, 2)->nullable(); // Score out of 100
            $table->boolean('is_passed')->nullable();
            $table->text('feedback')->nullable(); // Employee feedback on training
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create training_assessments table
        Schema::create('training_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->constrained('training_programs')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // quiz, practical, presentation
            $table->decimal('passing_score', 5, 2)->default(70.00); // Default passing score is 70%
            $table->text('criteria')->nullable(); // Assessment criteria
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create employee_assessment_results table
        Schema::create('employee_assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_training_id')->constrained('employee_trainings')->onDelete('cascade');
            $table->foreignId('training_assessment_id')->constrained('training_assessments')->onDelete('cascade');
            $table->decimal('score', 5, 2);
            $table->boolean('is_passed');
            $table->text('feedback')->nullable();
            $table->date('assessment_date');
            $table->foreignId('assessed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_assessment_results');
        Schema::dropIfExists('training_assessments');
        Schema::dropIfExists('employee_trainings');
    }
};