<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['earning', 'deduction'])->default('earning');
            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('default_amount', 10, 2)->default(0);
            $table->decimal('percentage_of_basic', 5, 2)->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};