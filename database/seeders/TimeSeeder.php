<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeSeeder extends Seeder
{
    public function run()
    {
        $fields = DB::table('fields')
            ->join('sports', 'fields.sportId', '=', 'sports.sportId')
            ->select('fields.fieldId', 'fields.sportId', 'sports.sportName')
            ->get();
        $sportPrice = [
            'futsal' => 100000,
            'badminton' => 50000,
            'basketball' => 70000,
            'volleyball' => 60000,
            'tennis' => 110000,
            'sepak bola' => 120000,
        ];
        $startHour = 1;  // Mulai dari jam 01:00
        $endHour = 24;   // Sampai jam 24:00 (alias 00:00 keesokan harinya)

        $timeId = 1;

        foreach ($fields as $field) {
            for ($hour = $startHour; $hour <= $endHour; $hour++) {
                $status = ($hour < 6 || $hour == $endHour) ? 'Non-available' : 'Available';
                $price = $sportPrice[strtolower($field->sportName)] ?? 50000;
                $priceNight = $price/5;
                $price = ($hour < 19) ? $price + 0 : $price + $priceNight;

                DB::table('times')->insert([
                    'timeId'     => $timeId++,
                    'fieldId'    => $field->fieldId,
                    'time'       => Carbon::createFromTime($hour, 0)->format('H:i'), // tanpa detik
                    'status'     => $status,
                    'price'      => $price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
