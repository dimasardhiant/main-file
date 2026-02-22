<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->decimal('basic_salary', 10, 2)->nullable();
            $table->json('components')->nullable(); 
            $table->boolean('is_active')->default(true);
            $table->enum('calculation_status', ['pending', 'calculated'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint for employee
            $table->unique(['employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};