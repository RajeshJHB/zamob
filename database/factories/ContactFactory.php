<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => '',
            'first_name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'telephone_1' => fake()->numerify('082#######'),
            'telephone_2' => '',
            'email_address' => fake()->safeEmail(),
            'physical_address' => fake()->streetAddress(),
            'related_contact_id' => null,
        ];
    }
}
