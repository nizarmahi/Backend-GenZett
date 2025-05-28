<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeSeeder extends Seeder
{
    public function run()
    {
        $fields = DB::table('fields')->get();
        $startHour = 10;  // 06:00
        $endHour = 23;   // 23:00

        $timeId = 1;

        foreach ($fields as $field) {
            for ($hour = $startHour; $hour <= $endHour; $hour++) {
                DB::table('times')->insert([
                    'timeId'    => $timeId++,
                    'fieldId'   => $field->fieldId,
                    'time'      => Carbon::createFromTime($hour, 0, 0)->format('H:i:s'),
                    'status'    => 'Available',
                    'price'     => rand(5, 10) * 10000,
                    'created_at'=> Carbon::now(),
                    'updated_at'=> Carbon::now(),
                ]);
            }
        }
    }
}
