<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('job_postings')->onDelete('cascade');
            $table->date('offer_date');
            $table->string('position');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->decimal('salary', 15, 2);
            $table->decimal('bonus', 15, 2)->nullable();
            $table->string('equity')->nullable();
            $table->text('benefits')->nullable();
            $table->date('start_date');
            $table->date('expiration_date');
            $table->string('offer_letter_path')->nullable();
            $table->enum('status', ['Draft', 'Sent', 'Accepted', 'Negotiating', 'Declined', 'Expired'])->default('Draft');
            $table->date('response_date')->nullable();
            $table->text('decline_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};