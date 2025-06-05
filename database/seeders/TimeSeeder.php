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
        $startHour = 1;  // Mulai dari jam 01:00
        $endHour = 24;   // Sampai jam 24:00 (alias 00:00 keesokan harinya)

        $timeId = 1;

        foreach ($fields as $field) {
            for ($hour = $startHour; $hour <= $endHour; $hour++) {
                $status = ($hour < 6) ? 'Non-available' : 'Available';

                DB::table('times')->insert([
                    'timeId'     => $timeId++,
                    'fieldId'    => $field->fieldId,
                    'time'       => Carbon::createFromTime($hour, 0)->format('H:i'), // tanpa detik
                    'status'     => $status,
                    'price'      => rand(5, 10) * 10000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
