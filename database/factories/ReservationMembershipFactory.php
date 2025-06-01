<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'reservationId' => Reservation::factory(),
            'membershipId' => Membership::factory(),
        ];
    }
}
