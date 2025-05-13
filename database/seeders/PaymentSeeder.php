<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $payments = [
            ['reservationId' => 1, 'invoiceDate' => now(), 'totalPaid' => 50000],
            ['reservationId' => 2, 'invoiceDate' => now(), 'totalPaid' => 100000],
            ['reservationId' => 3, 'invoiceDate' => now(), 'totalPaid' => 250000],
            ['reservationId' => 4, 'invoiceDate' => now(), 'totalPaid' => 180000],
            ['reservationId' => 5, 'invoiceDate' => now(), 'totalPaid' => 300000],
        ];

        foreach ($payments as $payment) {
            DB::table('payments')->insert([
                ...$payment,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}
