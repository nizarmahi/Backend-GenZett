<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [];

        $locations = DB::table('locations')->pluck('locationName', 'locationId');

        // Futsal courts
        $futsalCourts = [
            [1, 4],
            [2, 2],
            [3, 1],
        ];
        foreach ($futsalCourts as [$locationId, $count]) {
            $locationName = $locations[$locationId] ?? "Unknown Location";

            for ($i = 1; $i <= $count; $i++) {
                $fields[] = [
                    'locationId' => $locationId,
                    'sportId' => 1,
                    'name' => "Futsal - Court {$i}",
                    'description' => 'Indoor',
                ];
            }
        }

        // Badminton courts
        $badmintonCourts = [
            [1, 2],
            [2, 4],
            [4, 3],
        ];
        foreach ($badmintonCourts as [$locationId, $count]) {
            $locationName = $locations[$locationId] ?? "Unknown Location";

            for ($i = 1; $i <= $count; $i++) {
                $fields[] = [
                    'locationId' => $locationId,
                    'sportId' => 2,
                    'name' => "Badminton - Court {$i}",
                    'description' => 'Indoor',
                ];
            }
        }

        // Basketball courts
        $basketballCourts = [
            [3, 3],
            [4, 3],
            [5, 2],
        ];
        foreach ($basketballCourts as [$locationId, $count]) {
            $locationName = $locations[$locationId] ?? "Unknown Location";

            for ($i = 1; $i <= $count; $i++) {
                $fields[] = [
                    'locationId' => $locationId,
                    'sportId' => 3,
                    'name' => "Basketball - Court {$i}",
                    'description' => 'Indoor',
                ];
            }
        }

        // Volleyball courts
        $volleyballCourts = [
            [1, 2],
            [3, 2],
            [5, 1],
        ];
        foreach ($volleyballCourts as [$locationId, $count]) {
            $locationName = $locations[$locationId] ?? "Unknown Location";

            for ($i = 1; $i <= $count; $i++) {
                $fields[] = [
                    'locationId' => $locationId,
                    'sportId' => 4,
                    'name' => "Voli - Court {$i}",
                    'description' => 'Outdoor',
                ];
            }
        }

        // Tennis courts
        $tennisCourts = [
            [4, 2],
            [5, 4],
        ];
        foreach ($tennisCourts as [$locationId, $count]) {
            $locationName = $locations[$locationId] ?? "Unknown Location";

            for ($i = 1; $i <= $count; $i++) {
                $fields[] = [
                    'locationId' => $locationId,
                    'sportId' => 5,
                    'name' => "Tennis - Court {$i}",
                    'description' => 'Outdoor',
                ];
            }
        }

        // Handball courts (sportId = 6)
        $handballCourts = [
            [2, 2],
            [5, 1],
        ];
        foreach ($handballCourts as [$locationId, $count]) {
            $locationName = $locations[$locationId] ?? "Unknown Location";

            for ($i = 1; $i <= $count; $i++) {
                $fields[] = [
                    'locationId' => $locationId,
                    'sportId' => 6,
                    'name' => "Football - Court {$i}",
                    'description' => 'Indoor',
                ];
            }
        }

        // Sepak Bola / Football courts (sportId = 7)
        $footballCourts = [
            [1, 1],
            [4, 2],
        ];
        foreach ($footballCourts as [$locationId, $count]) {
            $locationName = $locations[$locationId] ?? "Unknown Location";

            for ($i = 1; $i <= $count; $i++) {
                $fields[] = [
                    'locationId' => $locationId,
                    'sportId' => 7,
                    'name' => "HandBall - Field {$i}",
                    'description' => 'Outdoor',
                ];
            }
        }

        // Insert to DB
        foreach ($fields as $field) {
            $locationName = $locations[$locationId] ?? "Unknown Location";

            DB::table('fields')->insert([
                ...$field,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
