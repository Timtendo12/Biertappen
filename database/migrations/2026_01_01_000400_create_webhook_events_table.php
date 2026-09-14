<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * The idempotency ledger.
         *
         * Lemon Squeezy retries any non-200 response (5s / 25s / 125s) and the
         * dashboard can resend manually, but — unlike Stripe — it sends no event
         * id. So the key is built from the event name plus the resource it
         * concerns, which is stable across redeliveries of the same logical
         * event. Inserting here first is what makes handling exactly-once.
         */
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('lemon_squeezy');
            $table->string('event_name', 64);
            $table->string('resource_id', 64);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_name', 'resource_id']);
            $table->index(['provider', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
