<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);                       // deck_creator
            $table->string('status', 16)->default('active');  // active | revoked
            $table->string('source', 16)->default('purchase'); // purchase | manual

            // The Lemon Squeezy order that granted this, as a plain id rather
            // than a foreign key: it keeps our schema independent of a
            // package-owned table, and a manual admin grant has no order at all.
            $table->string('lemon_squeezy_order_id', 64)->nullable()->index();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // The database, not application code, is what makes double-granting impossible.
            $table->unique(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
    }
};
