<?php

namespace App\Http\Requests;

use App\Models\NoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchNotesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $noteTypeId = $this->input('note_type_id');

        if ($noteTypeId !== null && $noteTypeId !== '' && ! is_array($noteTypeId)) {
            $this->merge([
                'note_type_id' => [$noteTypeId],
                'note_type_scope' => $this->input('note_type_scope', 'selected'),
            ]);
        }

        if ((string) $this->input('note_type_scope', '') === 'all') {
            $this->merge([
                'note_type_id' => null,
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'note_q' => ['nullable', 'string', 'max:255'],
            'note_status' => ['nullable', 'string', 'max:32'],
            'only_mine' => ['nullable', 'boolean'],
            'note_type_scope' => ['nullable', 'string', Rule::in(['all', 'selected'])],
            'note_type_id' => ['nullable', 'array'],
            'note_type_id.*' => ['integer', Rule::exists('note_types', 'id')],
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    public function startDate(): ?string
    {
        $value = $this->input('start_date');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function endDate(): ?string
    {
        $value = $this->input('end_date');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function hasDateFilter(): bool
    {
        return $this->startDate() !== null || $this->endDate() !== null;
    }

    public function onlyMine(): bool
    {
        if (! $this->has('only_mine')) {
            return true;
        }

        return $this->boolean('only_mine');
    }

    public function noteTypeScope(): string
    {
        if ((string) $this->input('note_type_scope', '') === 'all') {
            return 'all';
        }

        if ((string) $this->input('note_type_scope', '') === 'selected' || $this->hasNoteTypeIdsInInput()) {
            return 'selected';
        }

        return 'all';
    }

    public function filtersByNoteType(): bool
    {
        return $this->noteTypeScope() === 'selected' && $this->resolvedNoteTypeIds() !== [];
    }

    /**
     * @return list<int>
     */
    public function resolvedNoteTypeIds(): array
    {
        if ($this->noteTypeScope() !== 'selected') {
            return [];
        }

        $raw = $this->input('note_type_id');

        if (! is_array($raw)) {
            return [];
        }

        $ids = collect($raw)
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return NoteType::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            if ($this->noteTypeScope() !== 'selected') {
                return;
            }

            if ($this->resolvedNoteTypeIds() === []) {
                $validator->errors()->add(
                    'note_type_id',
                    'Select at least one note type, or choose All note types.'
                );
            }
        });
    }

    private function hasNoteTypeIdsInInput(): bool
    {
        $raw = $this->input('note_type_id');

        if (is_array($raw)) {
            return collect($raw)->contains(fn (mixed $value): bool => (int) $value > 0);
        }

        return $raw !== null && $raw !== '' && (int) $raw > 0;
    }
}
