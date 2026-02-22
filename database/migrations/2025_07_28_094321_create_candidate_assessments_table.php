<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('assessment_name');
            $table->integer('score')->nullable();
            $table->integer('max_score')->nullable();
            $table->enum('pass_fail_status', ['Pass', 'Fail', 'Pending'])->default('Pending');
            $table->text('comments')->nullable();
            $table->foreignId('conducted_by')->constrained('users')->onDelete('cascade');
            $table->date('assessment_date');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_assessments');
    }
};