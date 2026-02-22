<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('document_categories')->onDelete('cascade');
            $table->longText('template_content');
            $table->json('placeholders')->nullable();
            $table->json('default_values')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('file_format')->default('pdf');
            $table->string('status')->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};