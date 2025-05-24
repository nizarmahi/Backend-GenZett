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
        Schema::create('times', function (Blueprint $table) {
            $table->id('timeId');
            $table->unsignedBigInteger('fieldId');
            $table->foreign('fieldId')->references('fieldId')->on('fields')->onDelete('cascade');
            $table->time('time')->nullable();
            $table->enum('status', ['available', 'booked'])->nullable();
            $table->integer('price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('times');
    }
};
