<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('type_id')->constrained('meeting_types')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('meeting_rooms')->onDelete('set null');
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration');
            $table->text('agenda')->nullable();
            $table->enum('status', ['Scheduled', 'In Progress', 'Completed', 'Cancelled'])->default('Scheduled');
            $table->enum('recurrence', ['None', 'Daily', 'Weekly', 'Monthly'])->default('None');
            $table->date('recurrence_end_date')->nullable();
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};