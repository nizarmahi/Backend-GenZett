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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id('reservationId');
            // $table->foreignId('userId')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('userId');
            $table->foreign('userId')->references('userId')->on('users')->onDelete('cascade');
            $table->enum('status', ['upcoming', 'ongoing', 'completed'])->nullable();
            $table->string('name')->nullable();
            $table->enum('paymentStatus', ['pending', 'dp', 'complete', 'fail'])->nullable();
            $table->integer('total')->nullable();
            $table->integer('remaining')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
