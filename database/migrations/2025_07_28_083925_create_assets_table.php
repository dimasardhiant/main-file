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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('asset_type_id')->constrained('asset_types')->onDelete('restrict');
            $table->string('serial_number')->nullable();
            $table->string('asset_code')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->string('status')->default('available'); // available, assigned, under_maintenance, disposed
            $table->string('condition')->nullable(); // new, good, fair, poor
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('supplier')->nullable();
            $table->string('warranty_info')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->string('images')->nullable(); // For storing image paths
            $table->string('documents')->nullable(); // For storing document paths
            $table->string('qr_code')->nullable(); // For storing QR code path
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create asset_assignments table
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->date('checkout_date');
            $table->date('expected_return_date')->nullable();
            $table->date('checkin_date')->nullable();
            $table->string('checkout_condition')->nullable();
            $table->string('checkin_condition')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Create asset_maintenances table
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('maintenance_type'); // repair, preventive, calibration, etc.
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->text('details')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('supplier')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create asset_depreciations table
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('method'); // straight_line, reducing_balance, etc.
            $table->integer('useful_life_years');
            $table->decimal('salvage_value', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2);
            $table->date('last_calculated_date');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('asset_maintenances');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
    }
};