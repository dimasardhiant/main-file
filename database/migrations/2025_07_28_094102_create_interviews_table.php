<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('job_postings')->onDelete('cascade');
            $table->foreignId('round_id')->constrained('interview_rounds')->onDelete('cascade');
            $table->foreignId('interview_type_id')->constrained('interview_types')->onDelete('cascade');
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->integer('duration')->default(60);
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->json('interviewers');
            $table->enum('status', ['Scheduled', 'Completed', 'Cancelled', 'No-show'])->default('Scheduled');
            $table->boolean('feedback_submitted')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};