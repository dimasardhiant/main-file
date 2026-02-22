<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->string('topic');
            $table->longText('content');
            $table->enum('type', ['Discussion', 'Decision', 'Action Item', 'Note'])->default('Discussion');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('recorded_at');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};