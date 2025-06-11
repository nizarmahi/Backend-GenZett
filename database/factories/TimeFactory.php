<?php

namespace Database\Factories;

use App\Models\Time;
use App\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeFactory extends Factory
{
    protected $model = Time::class;

    public function definition()
    {
        return [
            // Pastikan Field sudah ada, pakai factory atau ambil existing ID
            'fieldId' => Field::factory(),

            // waktu dalam format 'HH:MM:SS'
            'time' => $this->faker->time('H:i:s'),

            // status contoh: 'available', 'booked', 'unavailable'
            'status' => $this->faker->randomElement(['available', 'non-available']),

            // harga, misal antara 10000 - 50000
            'price' => $this->faker->numberBetween(10000, 50000),
        ];
    }
}
