<?php

namespace Database\Factories;

use App\Models\ReservationDetail;
use App\Models\Reservation;
use App\Models\Field;
use App\Models\Time;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationDetailFactory extends Factory
{
    protected $model = ReservationDetail::class;

    public function definition(): array
    {
        return [
            'reservationId' => Reservation::factory(),
            'fieldId' => Field::factory(),
            'timeId' => Time::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        ];
    }
}
