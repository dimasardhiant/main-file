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
        Schema::table('users', function (Blueprint $table) {
            if (isSaas() && Schema::hasColumn('users', 'plan_id')) {
                $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (isSaas() && Schema::hasColumn('users', 'plan_id')) {
                $table->dropForeign(['plan_id']);
            }
        });
    }
};
