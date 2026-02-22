<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->enum('accrual_type', ['monthly', 'yearly'])->default('yearly');
            $table->decimal('accrual_rate', 8, 2)->default(0);
            $table->integer('carry_forward_limit')->default(0);
            $table->integer('min_days_per_application')->default(1);
            $table->integer('max_days_per_application')->default(30);
            $table->boolean('requires_approval')->default(true);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};