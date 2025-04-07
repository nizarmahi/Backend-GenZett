<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

use App\Models\User;

class ResportsSeeder extends Seeder
{
    public function run()
    {
        // Insert Sports
        DB::table('sports')->insert([
            ['sportId' => 1, 'sportName' => 'badminton', 'description' => 'Olahraga futsal 5v5', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sportId' => 2, 'sportName' => 'basket', 'description' => 'Olahraga bola basket', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Locations
        DB::table('locations')->insert([
            ['locationId' => 1, 'locationName' => 'Lapangan A', 'description' => 'Lapangan indoor dengan fasilitas lengkap', 'locationPath' => 'images/lapangan_a.jpg', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['locationId' => 2, 'locationName' => 'Lapangan B', 'description' => 'Lapangan outdoor dengan rumput sintetis', 'locationPath' => 'images/lapangan_b.jpg', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Fields
        DB::table('fields')->insert([
            ['fieldId' => 1, 'locationId' => 1, 'sportId' => 1, 'name' => 'Futsal Court', 'description' => 'Lapangan futsal standar nasional', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['fieldId' => 2, 'locationId' => 2, 'sportId' => 2, 'name' => 'Basketball Court', 'description' => 'Lapangan basket full court', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Times
        DB::table('times')->insert([
            ['timeId' => 1, 'fieldId' => 1, 'time' => '08:00:00', 'status' => 'Available', 'price' => 100000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['timeId' => 2, 'fieldId' => 2, 'time' => '10:00:00', 'status' => 'Available', 'price' => 120000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Users
        DB::table('users')->insert([
            ['userId' => 1, 'username' => 'johndoe', 'name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '081234567890', 'password' => Hash::make('password123'), 'level' => 'Member', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['userId' => 2, 'username' => 'admin', 'name' => 'Admin', 'email' => 'admin@gmail.com', 'phone' => '089876543210', 'password' => Hash::make('admin123'), 'level' => 'Admin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Reservations
        DB::table('reservations')->insert([
            ['reservationId' => 1, 'userId' => 1, 'name' => 'Booking Futsal', 'status' => 'completed', 'paymentStatus' => 'complete', 'total' => 100000, 'remaining' => 0, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['reservationId' => 2, 'userId' => 2, 'name' => 'Booking Basket', 'status' => 'ongoing', 'paymentStatus' => 'dp', 'total' => 120000, 'remaining' => 120000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Reservation Details
        DB::table('reservation_details')->insert([
            ['detailId' => 1, 'reservationId' => 1, 'fieldId' => 1, 'timeId' => 1, 'date' => Carbon::now(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['detailId' => 2, 'reservationId' => 2, 'fieldId' => 2, 'timeId' => 2, 'date' => Carbon::now(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Payments
        DB::table('payments')->insert([
            ['paymentId' => 1, 'reservationId' => 1, 'invoiceDate' => Carbon::now(), 'totalPaid' => 100000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['paymentId' => 2, 'reservationId' => 2, 'invoiceDate' => Carbon::now(), 'totalPaid' => 0, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Admins
        DB::table('admins')->insert([
            ['adminId' => 1, 'userId' => 2, 'locationId' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Insert Members
        DB::table('members')->insert([
            ['memberId' => 1, 'userId' => 1, 'valid_until' => Carbon::now()->addYear(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
