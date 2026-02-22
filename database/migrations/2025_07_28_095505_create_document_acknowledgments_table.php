<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('hr_documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['Pending', 'Acknowledged', 'Overdue', 'Exempted'])->default('Pending');
            $table->timestamp('acknowledged_at')->nullable();
            $table->date('due_date')->nullable();
            $table->text('acknowledgment_note')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['document_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_acknowledgments');
    }
};