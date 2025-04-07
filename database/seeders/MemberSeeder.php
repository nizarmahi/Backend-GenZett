<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MemberSeeder extends Seeder
{
    public function run()
    {
        DB::table('members')->insert([
            ['memberId' => 1, 'userId' => 1, 'valid_until' => Carbon::now()->addYear(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
