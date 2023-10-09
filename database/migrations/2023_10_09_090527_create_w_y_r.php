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
        Schema::create('wyr', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->string('option1');
            $table->string('option2');
            $table->unsignedBigInteger('pack_id');
            $table->foreign('pack_id')->references('id')->on('packs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wyr', function (Blueprint $table) {
            //
        });
    }
};
