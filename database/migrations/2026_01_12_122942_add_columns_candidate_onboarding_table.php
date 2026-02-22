<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('candidate_onboarding')) {
            // Check if foreign key exists using information_schema
            $foreignKeyExists = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'candidate_onboarding' AND CONSTRAINT_NAME LIKE '%candidate_id%'"
            );
            
            if (!empty($foreignKeyExists)) {
                DB::statement('ALTER TABLE candidate_onboarding DROP FOREIGN KEY ' . $foreignKeyExists[0]->CONSTRAINT_NAME);
            }
            
            Schema::table('candidate_onboarding', function (Blueprint $table) {
                // Make candidate_id nullable if column exists
                if (Schema::hasColumn('candidate_onboarding', 'candidate_id')) {
                    $table->unsignedBigInteger('candidate_id')->nullable()->change();
                }

                // Add employee_id column with foreign key if it doesn't exist
                if (!Schema::hasColumn('candidate_onboarding', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable()->after('candidate_id');
                    $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('candidate_onboarding')) {
            // Check if employee_id foreign key exists
            $foreignKeyExists = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'candidate_onboarding' AND CONSTRAINT_NAME LIKE '%employee_id%'"
            );
            
            if (!empty($foreignKeyExists)) {
                DB::statement('ALTER TABLE candidate_onboarding DROP FOREIGN KEY ' . $foreignKeyExists[0]->CONSTRAINT_NAME);
            }
            
            Schema::table('candidate_onboarding', function (Blueprint $table) {
                // Drop employee_id column if it exists
                if (Schema::hasColumn('candidate_onboarding', 'employee_id')) {
                    $table->dropColumn('employee_id');
                }

                // Restore candidate_id foreign key if column exists
                if (Schema::hasColumn('candidate_onboarding', 'candidate_id')) {
                    $table->unsignedBigInteger('candidate_id')->nullable(false)->change();
                    $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
                }
            });
        }
    }
};