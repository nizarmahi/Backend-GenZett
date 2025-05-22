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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('paymentId');
            $table->unsignedBigInteger('reservationId');
            $table->foreign('reservationId')->references('reservationId')->on('reservations')->onDelete('cascade');
            $table->dateTime('invoiceDate')->nullable();
            $table->integer('totalPaid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
