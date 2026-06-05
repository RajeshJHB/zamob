<?php

namespace Database\Factories;

use App\Models\ContactCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactCategory>
 */
class ContactCategoryFactory extends Factory
{
    protected $model = ContactCategory::class;

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
