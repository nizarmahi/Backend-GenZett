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
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('paymentStatus', [
                'pending', 'dp', 'complete', 'fail', 'closed',
                'canceled', 'waiting', 'refund'
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('paymentStatus', [
                'pending', 'dp', 'complete', 'fail', 'closed'
            ])->nullable()->change();
        });
    }
};

