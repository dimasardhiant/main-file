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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date')->nullable(); // For multi-day holidays
            $table->string('category'); // national, religious, company-specific, etc.
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(false); // For annual recurring holidays
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_half_day')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create holiday_branch pivot table for location-based holidays
        Schema::create('holiday_branch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->constrained('holidays')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_branch');
        Schema::dropIfExists('holidays');
    }
};