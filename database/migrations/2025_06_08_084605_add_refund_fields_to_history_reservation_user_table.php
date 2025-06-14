<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRefundFieldsToHistoryReservationUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('history_reservation_user', function (Blueprint $table) {
            // Kolom untuk informasi refund
            $table->text('adminNote')->nullable()->after('cancelReason');
            $table->text('rejectReason')->nullable()->after('adminNote');
            
            // Kolom untuk jumlah refund dan waktu proses
            $table->integer('refundAmount')->default(0)->nullable()->after('rejectReason');
            $table->timestamp('processedAt')->nullable()->after('refundAmount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('history_reservation_user', function (Blueprint $table) {
            $table->dropColumn([
                'bankName',
                'accountName', 
                'accountNumber',
                'cancelReason',
                'adminNote',
                'rejectReason',
                'refundAmount',
                'processedAt'
            ]);
        });
    }
}