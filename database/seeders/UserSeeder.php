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
        DB::table('users')->insert([
            ['userId' => 1, 'username' => 'johndoe', 'name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '081234567890', 'password' => Hash::make('password123'), 'level' => 'Member', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['userId' => 2, 'username' => 'admin', 'name' => 'Admin', 'email' => 'admin@gmail.com', 'phone' => '089876543210', 'password' => Hash::make('admin123'), 'level' => 'Admin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
