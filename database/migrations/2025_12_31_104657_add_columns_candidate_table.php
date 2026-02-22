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
        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                if (! Schema::hasColumn('candidates', 'gender')) {
                    $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('phone');
                }
                if (! Schema::hasColumn('candidates', 'date_of_birth')) {
                    $table->date('date_of_birth')->nullable()->after('gender');
                }
                if (! Schema::hasColumn('candidates', 'address')) {
                    $table->string('address')->nullable()->after('date_of_birth');
                }
                if (! Schema::hasColumn('candidates', 'city')) {
                    $table->string('city')->nullable()->after('address');
                }
                if (! Schema::hasColumn('candidates', 'state')) {
                    $table->string('state')->nullable()->after('city');
                }
                if (! Schema::hasColumn('candidates', 'zip_code')) {
                    $table->string('zip_code')->nullable()->after('state');
                }
                if (! Schema::hasColumn('candidates', 'country')) {
                    $table->string('country')->nullable()->after('zip_code');
                }
                if (! Schema::hasColumn('candidates', 'coverletter_message')) {
                    $table->text('coverletter_message')->nullable()->after('cover_letter_path');
                }
                if (! Schema::hasColumn('candidates', 'rating')) {
                    $table->integer('rating')->nullable()->after('coverletter_message');
                }
                if (! Schema::hasColumn('candidates', 'is_archive')) {
                    $table->boolean('is_archive')->nullable()->after('rating');
                }
                if (! Schema::hasColumn('candidates', 'is_employee')) {
                    $table->boolean('is_employee')->default(0)->nullable()->after('is_archive');
                }
                if (! Schema::hasColumn('candidates', 'custom_question')) {
                    $table->json('custom_question')->nullable()->after('is_employee');
                }
                if (! Schema::hasColumn('candidates', 'terms_condition_check')) {
                    $table->enum('terms_condition_check', ['on', 'off'])->nullable()->after('custom_question');
                }
                if (! Schema::hasColumn('candidates', 'final_salary')) {
                    $table->decimal('final_salary', 15, 2)->nullable()->after('expected_salary');
                }
                if (! Schema::hasColumn('candidates', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('source_id');
                    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                }
                if (! Schema::hasColumn('candidates', 'department_id')) {
                    $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
                    $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                $columnsToCheck = [
                    'gender', 'date_of_birth', 'address', 'city', 'state',
                    'zip_code', 'country', 'coverletter_message', 'rating',
                    'is_archive', 'is_employee', 'custom_question', 'terms_condition_check',
                    'final_salary', 'branch_id', 'department_id'
                ];

                foreach ($columnsToCheck as $column) {
                    if (Schema::hasColumn('candidates', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
