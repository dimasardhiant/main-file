<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('onboarding_checklists')->onDelete('cascade');
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->enum('category', ['Documentation', 'IT Setup', 'Training', 'HR', 'Facilities', 'Other'])->default('Other');
            $table->string('assigned_to_role')->nullable();
            $table->integer('due_day')->default(1);
            $table->boolean('is_required')->default(true);
            $table->string('status')->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};