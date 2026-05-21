<?php

namespace Database\Factories;

use App\Models\NoteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoteType>
 */
class NoteTypeFactory extends Factory
{
    protected $model = NoteType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }
}
