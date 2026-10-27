<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('opened_at');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->decimal('expected_balance', 15, 2)->nullable();
            $table->decimal('closing_balance', 15, 2)->nullable(); // counted
            $table->string('status', 20)->default('open')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('payment_no', 40)->unique();
            $table->string('direction', 3); // in = received, out = paid
            $table->morphs('payable'); // Sale, Purchase
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 20);
            $table->string('reference')->nullable();
            $table->date('paid_on');
            $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'paid_on']);
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->string('reference_no', 40)->unique();
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('payment_method', 20);
            $table->string('paid_to')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'expense_date']);
        });

        // Money ledger. Every payment, refund and expense posts one row here.
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('transaction_date');
            $table->string('type', 30)->index();
            $table->string('direction', 3);
            $table->decimal('amount', 15, 2);
            $table->string('method', 20);
            $table->string('account', 20); // cash | bank
            $table->nullableMorphs('source');
            $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'transaction_date']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 50);
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('cash_registers');
    }
};
