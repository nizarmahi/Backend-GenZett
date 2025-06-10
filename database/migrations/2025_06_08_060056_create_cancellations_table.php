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
        Schema::create('cancellations', function (Blueprint $table) {
            $table->id('cancellationId');
            $table->unsignedBigInteger('reservationId');
            $table->string('accountName');
            $table->string('accountNumber');
            $table->enum('paymentPlatform', ['gopay', 'transferBank', 'ovo', 'dana']);
            $table->text('reason');
            $table->timestamps();

            $table->foreign('reservationId')->references('reservationId')->on('reservations')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellations');
    }
};
