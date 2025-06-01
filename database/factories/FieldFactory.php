<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\Location;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class FieldFactory extends Factory
{
    protected $model = Field::class;

    public function definition(): array
    {
        return [
            'name' => 'Lapangan ' . fake()->word(),
            'locationId' => Location::factory(),
            'sportId' => Sport::factory(),
        ];
    }
}
