<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained('interviews')->onDelete('cascade');
            $table->string('interviewer_id')->nullable();
            $table->integer('technical_rating')->nullable();
            $table->integer('communication_rating')->nullable();
            $table->integer('cultural_fit_rating')->nullable();
            $table->integer('overall_rating')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('comments')->nullable();
            $table->enum('recommendation', ['Strong Hire', 'Hire', 'Maybe', 'Reject', 'Strong Reject'])->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_feedback');
    }
};