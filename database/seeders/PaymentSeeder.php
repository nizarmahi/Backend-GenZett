<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        DB::table('payments')->insert([
            ['paymentId' => 1, 'reservationId' => 1, 'invoiceDate' => Carbon::now(), 'totalPaid' => 100000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['paymentId' => 2, 'reservationId' => 2, 'invoiceDate' => Carbon::now(), 'totalPaid' => 0, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
