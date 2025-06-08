<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryReservationUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('history_reservation_user', function (Blueprint $table) {
            $table->id('historyId');
            $table->unsignedBigInteger('reservationId');
            $table->unsignedBigInteger('userId');
            $table->string('bookingName');
            $table->string('cabang');
            $table->string('lapangan');
            $table->string('paymentStatus'); // 'DP', 'Lunas', 'canceled', 'waiting'
            $table->string('paymentType');
            $table->string('reservationStatus'); // 'Upcoming', 'Ongoing', 'Completed'
            $table->decimal('totalAmount', 10, 2);
            $table->decimal('totalPaid', 10, 2)->default(0);
            $table->decimal('remainingAmount', 10, 2)->default(0);
            $table->date('reservationDate');
            $table->json('details'); // Menyimpan detail reservasi dalam format JSON
            
            // Fields untuk refund (ketika cancel)
            $table->string('bankName')->nullable();
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
            $table->text('cancelReason')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('reservationId')->references('reservationId')->on('reservations')->onDelete('cascade');
            $table->foreign('userId')->references('userId')->on('users')->onDelete('cascade');
            
            // Index untuk performa
            $table->index(['userId', 'reservationStatus']);
            $table->index('reservationDate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('history_reservation_user');
    }
}