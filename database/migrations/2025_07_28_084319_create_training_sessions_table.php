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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->constrained('training_programs')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('location')->nullable(); // Physical or virtual location
            $table->string('location_type')->default('physical'); // physical, virtual
            $table->string('meeting_link')->nullable(); // For virtual sessions
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->text('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly
            $table->integer('recurrence_count')->nullable(); // Number of recurrences
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create training_session_trainer pivot table
        Schema::create('training_session_trainer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade'); // Trainer/instructor
            $table->timestamps();
        });

        // Create training_session_attendance table
        Schema::create('training_session_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_present')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_session_attendance');
        Schema::dropIfExists('training_session_trainer');
        Schema::dropIfExists('training_sessions');
    }
};