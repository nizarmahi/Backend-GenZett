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
        Schema::create('reservation_details', function (Blueprint $table) {
            $table->id('detailId');
            $table->unsignedBigInteger('reservationId');
            $table->foreign('reservationId')->references('reservationId')->on('reservations')->onDelete('cascade');
            $table->unsignedBigInteger('fieldId');
            $table->foreign('fieldId')->references('fieldId')->on('fields')->onDelete('cascade');
            $table->unsignedBigInteger('timeId');
            $table->foreign('timeId')->references('timeId')->on('times')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_details');
    }
};
