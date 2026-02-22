<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['Required', 'Optional'])->default('Required');
            $table->enum('rsvp_status', ['Pending', 'Accepted', 'Declined', 'Tentative'])->default('Pending');
            $table->enum('attendance_status', ['Not Attended', 'Present', 'Late', 'Left Early'])->default('Not Attended');
            $table->timestamp('rsvp_date')->nullable();
            $table->text('decline_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
    }
};