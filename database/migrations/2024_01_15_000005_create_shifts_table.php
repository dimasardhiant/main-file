<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_duration')->default(0); // minutes
            $table->time('break_start_time')->nullable(); // break start time
            $table->time('break_end_time')->nullable(); // break end time
            $table->integer('grace_period')->default(0); // minutes
            $table->boolean('is_night_shift')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};