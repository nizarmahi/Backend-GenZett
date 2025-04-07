<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    public function run()
    {
        DB::table('reservations')->insert([
            ['reservationId' => 1, 'userId' => 1, 'name' => 'Booking Futsal', 'status' => 'completed', 'paymentStatus' => 'complete', 'total' => 100000, 'remaining' => 0, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['reservationId' => 2, 'userId' => 2, 'name' => 'Booking Basket', 'status' => 'ongoing', 'paymentStatus' => 'dp', 'total' => 120000, 'remaining' => 120000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
