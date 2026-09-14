<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Null owner = system / base-game deck, curated by administrators.
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('locale', 5)->default('nl');

            $table->string('visibility')->default('private');   // private | shared | public
            $table->string('status')->default('draft');         // draft | published | hidden
            $table->boolean('featured')->default(false);

            $table->string('ending_mode')->default('cards');    // cards | all_cards_once
            $table->unsignedSmallInteger('ending_count')->nullable();

            // Random, revocable. Null until the owner shares the deck.
            $table->string('share_token', 64)->nullable()->unique();

            $table->json('tags')->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            // Drives the deck-selection query: published base decks, newest first.
            $table->index(['status', 'visibility', 'featured']);
            $table->index(['owner_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decks');
    }
};
