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
        Schema::create('reservation_member', function (Blueprint $table) {
            $table->id("reservationMemberId");
            $table->unsignedBigInteger('reservationId');
            $table->foreign('reservationId')->references('reservationId')->on('reservations')->onDelete('cascade');
            $table->unsignedBigInteger('membershipId');
            $table->foreign('membershipId')->references('membershipId')->on('memberships')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_member');
    }

    
};
