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
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'biometric_emp_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('biometric_emp_id')->nullable()->after('employee_id');
            });
        }

        if (Schema::hasTable('attendance_records') && !Schema::hasColumn('attendance_records', 'biometric_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->string('biometric_id')->nullable()->after('employee_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'biometric_emp_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('biometric_emp_id');
            });
        }

        if (Schema::hasTable('attendance_records') && Schema::hasColumn('attendance_records', 'biometric_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropColumn('biometric_id');
            });
        }
    }
};
