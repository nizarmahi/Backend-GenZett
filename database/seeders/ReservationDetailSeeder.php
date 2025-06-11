<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationDetailSeeder extends Seeder
{
    public function run(): void
    {
        $details = [];

        $months = [
            ['start' => '2025-03-01', 'end' => '2025-03-31'],
            ['start' => '2025-04-01', 'end' => '2025-04-30'],
            ['start' => '2025-05-01', 'end' => '2025-05-31'],
            ['start' => '2025-06-01', 'end' => '2025-06-19'],
        ];

        for ($reservationId = 1; $reservationId <= 50; $reservationId++) {
            // Pick random month
            $month = $months[array_rand($months)];
            $start = Carbon::parse($month['start']);
            $end = Carbon::parse($month['end']);

            // Random date within month
            $daysDiff = $start->diffInDays($end);
            $randomDays = rand(0, $daysDiff);
            $date = $start->copy()->addDays($randomDays);

            // Field and time slots (1-3 consecutive slots)
            $fieldId = rand(1, 10);
            $timeSlotCount = rand(1, 3);
            $availableTimes = DB::table('times')
                ->where('status', 'available')
                ->where('fieldId', $fieldId)
                ->pluck('timeId')
                ->toArray();

            $maxIndex = count($availableTimes) - $timeSlotCount;
            if ($maxIndex < 0) {
                continue;
            }

            $startIndex = $availableTimes[rand(0, $maxIndex)];

            for ($j = 0; $j < $timeSlotCount; $j++) {
                $details[] = [
                    'reservationId' => $reservationId,
                    'fieldId' => $fieldId,
                    'timeId' => $startIndex,
                    'date' => $date->format('Y-m-d'),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        DB::table('reservation_details')->insert($details);
    }
}
