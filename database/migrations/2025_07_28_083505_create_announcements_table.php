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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category'); // company news, policy updates, events, etc.
            $table->text('description')->nullable();
            $table->longText('content');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('attachments')->nullable(); // For storing attachment paths
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_high_priority')->default(false);
            $table->boolean('is_company_wide')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create announcement_department pivot table
        Schema::create('announcement_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->timestamps();
        });

        // Create announcement_branch pivot table
        Schema::create('announcement_branch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->timestamps();
        });

        // Create announcement_views table to track which employees have viewed announcements
        Schema::create('announcement_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('viewed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_views');
        Schema::dropIfExists('announcement_branch');
        Schema::dropIfExists('announcement_department');
        Schema::dropIfExists('announcements');
    }
};