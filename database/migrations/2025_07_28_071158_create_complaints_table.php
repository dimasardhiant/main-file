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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade'); // Complainant
            $table->foreignId('against_employee_id')->nullable()->constrained('users')->nullOnDelete(); // Against whom
            $table->string('complaint_type'); // harassment, discrimination, workplace conditions, etc.
            $table->string('subject');
            $table->date('complaint_date');
            $table->text('description')->nullable();
            $table->string('status')->default('submitted'); // submitted, under investigation, resolved, dismissed
            $table->string('documents')->nullable(); // For storing document paths
            $table->boolean('is_anonymous')->default(false);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // HR personnel assigned
            $table->date('resolution_deadline')->nullable();
            $table->text('investigation_notes')->nullable();
            $table->text('resolution_action')->nullable();
            $table->date('resolution_date')->nullable();
            $table->text('follow_up_action')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};