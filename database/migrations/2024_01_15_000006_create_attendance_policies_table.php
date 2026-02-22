<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('late_arrival_grace')->default(15); // minutes
            $table->integer('early_departure_grace')->default(15); // minutes
            $table->decimal('half_day_threshold', 5, 2)->default(4.00); // hours
            $table->decimal('overtime_rate_per_hour', 8, 2)->default(150.00); // ₹150 per hour
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};