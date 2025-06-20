<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'locationName' => fake()->company(),
            'address' => fake()->address(),
            'description' => fake()->paragraph(),
            'locationPath' => 'locations/fake-image.jpg', // default path, bisa diganti di test pakai Storage::fake
        ];
    }
}
