<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            // Optional login account; sales made by this user count towards targets.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('employee_code', 30)->unique();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('designation', 100)->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('monthly_salary', 15, 2)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->string('status', 20); // present, absent, half_day, leave, holiday
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index(['branch_id', 'date']);
        });

        Schema::create('staff_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('period'); // first day of the month
            $table->decimal('target_amount', 15, 2);
            $table->string('incentive_type', 10)->default('percent'); // percent of achieved sales | fixed
            $table->decimal('incentive_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'period']);
        });

        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_target_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->date('period');
            $table->string('type', 20); // target_bonus, commission, performance, other
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete(); // posted when paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentives');
        Schema::dropIfExists('staff_targets');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
    }
};
