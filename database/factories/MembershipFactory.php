<?php

namespace Database\Factories;

use App\Models\Membership;
use App\Models\Location;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'locationId' => Location::factory(), // otomatis buat Location jika belum ada
            'sportId' => Sport::factory(),       // otomatis buat Sport jika belum ada
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'discount' => $this->faker->randomFloat(2, 15, 5), // harga antara 10rb–100rb
            'weeks' => $this->faker->numberBetween(1, 12), // 1–12 minggu
        ];
    }
}
