<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Donations live here rather than in lemon_squeezy_orders.
         *
         * Two reasons, both structural. The package's orders table declares
         * billable via $table->morphs(), which is NOT nullable, and its webhook
         * handler throws InvalidCustomPayload when custom_data carries no
         * billable_id. A donation from a logged-out visitor has neither — and
         * "Doneer een biertje" must work without an account.
         *
         * A donation never grants anything. Keeping it in its own table makes
         * that separation impossible to blur.
         */
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            // Unique: a redelivered order_created can never double-record a gift.
            $table->string('lemon_squeezy_order_id', 64)->unique();

            // Nullable by design: guests can donate.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 16)->default('paid'); // paid | refunded
            $table->string('email')->nullable();
            $table->string('order_number')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
