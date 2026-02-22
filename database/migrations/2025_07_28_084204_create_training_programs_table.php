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
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('training_type_id')->constrained('training_types')->onDelete('restrict');
            $table->text('description')->nullable();
            $table->integer('duration')->nullable(); // Duration in hours
            $table->decimal('cost', 15, 2)->nullable();
            $table->integer('capacity')->nullable();
            $table->string('status')->default('draft'); // draft, active, completed, cancelled
            $table->string('materials')->nullable(); // For storing training materials paths
            $table->text('prerequisites')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_self_enrollment')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_programs');
    }
};