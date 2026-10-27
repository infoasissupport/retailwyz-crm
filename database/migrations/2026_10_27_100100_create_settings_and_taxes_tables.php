<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 30)->nullable()->unique();
            $table->decimal('rate', 6, 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Document numbering (invoices, purchase refs, returns...), incremented under row lock.
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('settings');
    }
};
