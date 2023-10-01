<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->unsignedBigInteger('pack_id'); // the pack that the question belongs to
            $table->foreign('pack_id')->references('id')->on('packs');
            $table->integer('mentionAmount')->default(1); // amount of names that are mentioned
            $table->boolean('nsfw')->default(false); // is the question nsfw. Not sure if Im going to use this.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
