<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $reservations = [
            ['userId' => 6, 'name' => 'Booking 1', 'paymentStatus' => 'pending', 'total' => 150000],
            ['userId' => 7, 'name' => 'Booking 2', 'paymentStatus' => 'dp', 'total' => 200000],
            ['userId' => 8, 'name' => 'Booking 3', 'paymentStatus' => 'complete', 'total' => 250000],
            ['userId' => 9, 'name' => 'Booking 4', 'paymentStatus' => 'pending', 'total' => 180000],
            ['userId' => 10, 'name' => 'Booking 5', 'paymentStatus' => 'complete', 'total' => 300000],
        ];

        foreach ($reservations as $res) {
            DB::table('reservations')->insert([
                ...$res,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}
