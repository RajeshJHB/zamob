<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\NoteType;
use App\Models\ServiceNote;
use App\Models\User;
use App\Support\ServiceNoteNumberAssigner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceNote>
 */
class ServiceNoteFactory extends Factory
{
    protected $model = ServiceNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'note_number' => fn () => ServiceNoteNumberAssigner::nextNumber(),
            'note_type_id' => fn () => NoteType::query()->orderBy('sort_order')->value('id'),
            'status' => ServiceNote::STATUS_OPEN,
            'heading' => fake()->sentence(4),
            'noted_at' => now(),
            'body' => fake()->text(200),
            'attachment_path' => null,
            'attachment_original_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'created_by' => User::factory(),
            'staff' => '',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceNote::STATUS_CLOSED,
        ]);
    }
}
