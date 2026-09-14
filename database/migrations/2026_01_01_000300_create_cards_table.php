<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();

            // Free-form slug; new card types never require a migration or schema change.
            $table->string('type', 50)->default('custom');

            // Split rather than polymorphic so "cards this roster can play" stays an indexed WHERE.
            $table->string('participants_mode', 8)->default('count'); // count | all
            $table->unsignedTinyInteger('participants_count')->nullable();

            // Structured content segments; never a rendered string.
            $table->json('content');

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['deck_id', 'position']);
            $table->index(['deck_id', 'participants_mode', 'participants_count'], 'cards_eligibility_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
