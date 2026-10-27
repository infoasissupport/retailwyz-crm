<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('delivery_no', 40)->unique();
            $table->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot of who/where, so later customer edits don't rewrite delivery history.
            $table->string('customer_name');
            $table->string('phone', 30);
            $table->text('address');
            $table->date('scheduled_date')->index();
            $table->string('time_slot', 50)->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete(); // driver from staff
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 30)->nullable();
            $table->string('vehicle_no', 30)->nullable();
            $table->decimal('order_value', 15, 2)->default(0);
            $table->decimal('delivery_charge', 15, 2)->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('received_by')->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
