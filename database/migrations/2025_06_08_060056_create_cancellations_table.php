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
            $table->string('accountName',30);
            $table->string('accountNumber',25);
            $table->string('paymentPlatform',20);
            $table->text('reason',50);
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
