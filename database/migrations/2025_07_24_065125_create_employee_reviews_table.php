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
        Schema::create('employee_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('review_cycle_id')->constrained('review_cycles')->onDelete('cascade');
            $table->date('review_date');
            $table->date('completion_date')->nullable();
            $table->decimal('overall_rating', 3, 1)->nullable();
            $table->text('comments')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Table for storing individual indicator ratings
        Schema::create('employee_review_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_review_id')->constrained()->onDelete('cascade');
            $table->foreignId('performance_indicator_id')->constrained()->onDelete('cascade');
            $table->decimal('rating', 3, 1);
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_review_ratings');
        Schema::dropIfExists('employee_reviews');
    }
};
