<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->truncate();
        DB::table('users')->insert([
            ['userId' => 1, 'username' => 'admin1', 'name' => 'Admin1', 'email' => 'admin1@gmail.com', 'phone' => '089876543210', 'password' => Hash::make('admin123'), 'role' => 'admin', 'created_at' => Carbon::now()],
            ['userId' => 2, 'username' => 'admin2', 'name' => 'Admin2', 'email' => 'admin2@gmail.com', 'phone' => '089876543211', 'password' => Hash::make('admin123'), 'role' => 'admin', 'created_at' => Carbon::now()],
            ['userId' => 3, 'username' => 'admin3', 'name' => 'Admin3', 'email' => 'admin3@gmail.com', 'phone' => '089876543212', 'password' => Hash::make('admin123'), 'role' => 'admin', 'created_at' => Carbon::now()],
            ['userId' => 4, 'username' => 'admin4', 'name' => 'Admin4', 'email' => 'admin4@gmail.com', 'phone' => '089876543213', 'password' => Hash::make('admin123'), 'role' => 'admin', 'created_at' => Carbon::now()],
            ['userId' => 5, 'username' => 'admin5', 'name' => 'Admin5', 'email' => 'admin5@gmail.com', 'phone' => '089876543214', 'password' => Hash::make('admin123'), 'role' => 'admin', 'created_at' => Carbon::now()],
        ]);

        $users = [
            ['username' => 'user01', 'name' => 'Ali', 'email' => 'ali@mail.com', 'phone' => '0811111111'],
            ['username' => 'user02', 'name' => 'Budi', 'email' => 'budi@mail.com', 'phone' => '0812222222'],
            ['username' => 'user03', 'name' => 'Citra', 'email' => 'citra@mail.com', 'phone' => '0813333333'],
            ['username' => 'user04', 'name' => 'Dewi', 'email' => 'dewi@mail.com', 'phone' => '0814444444'],
            ['username' => 'user05', 'name' => 'Eka', 'email' => 'eka@mail.com', 'phone' => '0815555555'],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert([
                ...$user,
                'password' => bcrypt('password'),
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
