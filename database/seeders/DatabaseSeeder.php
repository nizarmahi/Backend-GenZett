<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            SportSeeder::class,
            LocationSeeder::class,
            FieldSeeder::class,
            UserSeeder::class,
            MemberSeeder::class,
            AdminSeeder::class,
            TimeSeeder::class,
            ReservationSeeder::class,
            ReservationDetailSeeder::class,
            PaymentSeeder::class,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
