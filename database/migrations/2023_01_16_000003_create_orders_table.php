<?php

/*
 * Published from lemonsqueezy/laravel so we own the schema.
 *
 * Publishing is required here rather than optional: the upstream migration is
 * incompatible with MariaDB in strict mode (see ordered_at below). The package
 * is told not to load its own copies via LemonSqueezy::ignoreMigrations() in
 * App\Providers\LemonSqueezyServiceProvider, otherwise both would run.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lemon_squeezy_orders', function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->string('lemon_squeezy_id')->unique();
            $table->string('customer_id');
            $table->uuid('identifier')->unique();
            $table->string('product_id')->index();
            $table->string('variant_id')->index();
            $table->integer('order_number')->unique();
            $table->string('currency');
            $table->integer('subtotal');
            $table->integer('discount_total');
            $table->integer('tax');
            $table->integer('total');
            $table->string('tax_name')->nullable();
            $table->string('status');
            $table->string('receipt_url')->nullable();
            $table->boolean('refunded');
            $table->timestamp('refunded_at')->nullable();
            // Nullable, unlike upstream: MariaDB rejects a TIMESTAMP NOT NULL with no
            // default under strict mode (error 1067). The package's Order::sync()
            // already guards this value with isset(), so null is handled.
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lemon_squeezy_orders');
    }
};
