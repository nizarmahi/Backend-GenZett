<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationDetailSeeder extends Seeder
{
    public function run()
    {
        DB::table('reservation_details')->insert([
            ['detailId' => 1, 'reservationId' => 1, 'fieldId' => 1, 'timeId' => 1, 'date' => Carbon::now(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['detailId' => 2, 'reservationId' => 2, 'fieldId' => 2, 'timeId' => 2, 'date' => Carbon::now(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
