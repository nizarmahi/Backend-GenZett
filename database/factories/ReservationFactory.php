<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition()
    {
        return [
            'userId' => User::factory(), // buat user baru atau bisa pakai existing userId
            'name' => $this->faker->name(),
            'paymentStatus' => $this->faker->randomElement(['pending', 'fail', 'complete']),
            'total' => $this->faker->numberBetween(10000, 500000),
        ];
    }
}
