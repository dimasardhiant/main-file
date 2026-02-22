<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('job_postings')->onDelete('cascade');
            $table->foreignId('source_id')->constrained('candidate_sources')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('current_company')->nullable();
            $table->string('current_position')->nullable();
            $table->integer('experience_years')->default(0);
            $table->decimal('current_salary', 15, 2)->nullable();
            $table->decimal('expected_salary', 15, 2)->nullable();
            $table->string('notice_period')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('cover_letter_path')->nullable();
            $table->text('skills')->nullable();
            $table->text('education')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->foreignId('referral_employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['New', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected'])->default('New');
            $table->date('application_date');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};