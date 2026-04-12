<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImeiModel>
 */
class ImeiModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'make' => 'FactoryMake',
            'model' => fake()->unique()->words(2, true),
            'serial' => fake()->optional()->numerify('########'),
        ];
    }
}
