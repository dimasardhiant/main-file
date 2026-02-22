<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('job_requisitions')->onDelete('cascade');
            $table->string('job_code')->unique();
            $table->string('title');
            $table->foreignId('job_type_id')->constrained('job_types')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('job_locations')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->integer('min_experience')->default(0);
            $table->integer('max_experience')->nullable();
            $table->decimal('min_salary', 15, 2)->nullable();
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->date('application_deadline')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('publish_date')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['Draft', 'Published', 'Closed'])->default('Draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};