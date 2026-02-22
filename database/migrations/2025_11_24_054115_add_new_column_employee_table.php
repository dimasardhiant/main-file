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
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'base_salary')) {
                    $table->decimal('base_salary', 10, 2)->nullable()->after('date_of_joining');
                }
                if (!Schema::hasColumn('employees', 'employee_status')) {
                    $table->enum('employee_status', ['active', 'inactive', 'terminated', 'probation'])
                        ->default('active')
                        ->after('employment_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasColumn('employees', 'base_salary')) {
                    $table->dropColumn('base_salary');
                }
                if (Schema::hasColumn('employees', 'employee_status')) {
                    $table->dropColumn('employee_status');
                }
            });
        }
    }
};
