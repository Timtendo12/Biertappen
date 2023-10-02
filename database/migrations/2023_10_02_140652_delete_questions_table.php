<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('questions');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->default(Uuid::uuid4()->toString());
            $table->string('task');
            $table->integer('players')->default(1); // f.e. if players is 2, it means that the question requires 2 names. ex: Jantje doe iets met Pieter
            $table->string('type')->default('normal');
            $table->unsignedBigInteger('pack_id');
            $table->foreign('pack_id')->references('id')->on('packs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
