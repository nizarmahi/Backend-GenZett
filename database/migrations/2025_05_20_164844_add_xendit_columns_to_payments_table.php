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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('xendit_invoice_id')->nullable()->after('totalPaid');
            $table->string('xendit_invoice_url')->nullable()->after('xendit_invoice_id');
            $table->string('xendit_status')->nullable()->after('xendit_invoice_url');
            $table->dateTime('expiry_date')->nullable()->after('xendit_status');
            $table->string('success_redirect_url')->nullable()->after('expiry_date');
            $table->string('failure_redirect_url')->nullable()->after('success_redirect_url');
            $table->string('payment_method')->nullable()->after('failure_redirect_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }
};
