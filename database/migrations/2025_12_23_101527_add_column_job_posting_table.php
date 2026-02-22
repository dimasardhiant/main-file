<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Check and drop foreign keys if they exist
            if (Schema::hasColumn('job_postings', 'requisition_id')) {
                $table->dropForeign(['requisition_id']);
                $table->dropColumn('requisition_id');
            }

            // Add new columns only if they don't exist
            if (!Schema::hasColumn('job_postings', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('location_id')->constrained('branches')->onDelete('set null');
            }

            if (!Schema::hasColumn('job_postings', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('branch_id')->constrained('departments')->onDelete('set null');
            }
            if (!Schema::hasColumn('job_postings', 'priority')) {
                $table->enum('priority', ['Low', 'Medium', 'High'])->default('Medium')->after('is_featured');
            }
            if (!Schema::hasColumn('job_postings', 'skills')) {
                $table->json('skills')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('job_postings', 'start_date')) {
                $table->date('start_date')->nullable()->after('benefits');
            }
            if (!Schema::hasColumn('job_postings', 'positions')) {
                $table->integer('positions')->default(1)->after('skills');
            }

            if (!Schema::hasColumn('job_postings', 'applicant')) {
                $table->json('applicant')->nullable()->after('positions');
            }
            if (!Schema::hasColumn('job_postings', 'visibility')) {
                $table->json('visibility')->nullable()->after('applicant');
            }
            if (!Schema::hasColumn('job_postings', 'code')) {
                $table->string('code')->unique()->nullable()->after('visibility');
            }
            if (!Schema::hasColumn('job_postings', 'custom_question')) {
                $table->json('custom_question')->nullable()->after('code');
            }
            if (!Schema::hasColumn('job_postings', 'application_type')) {
                $table->enum('application_type', ['existing', 'custom'])->default('existing')->after('custom_question');
            }
            if (!Schema::hasColumn('job_postings', 'application_url')) {
                $table->string('application_url')->nullable()->after('application_type');
            }

            // Modify existing columns
            $table->decimal('min_experience', 3, 1)->default(0)->change();
            $table->decimal('max_experience', 3, 1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Add back foreign key columns if they don't exist
            if (!Schema::hasColumn('job_postings', 'requisition_id')) {
                $table->foreignId('requisition_id')->constrained('job_requisitions')->onDelete('cascade');
            }
            if (!Schema::hasColumn('job_postings', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            }

            // Drop added columns if they exist
            $columnsToCheck = ['priority', 'skills', 'positions', 'start_date', 'applicant', 'visibility', 'code', 'custom_question', 'application_type', 'application_url', 'branch_id'];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('job_postings', $column)) {
                    if ($column === 'branch_id') {
                        $table->dropForeign(['branch_id']);
                    }
                    $table->dropColumn($column);
                }
            }

            // Revert column changes
            $table->integer('min_experience')->default(0)->change();
            $table->integer('max_experience')->nullable()->change();
        });
    }
};
