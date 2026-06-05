<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class ContactsTable
{
    /** @var array<string, string> */
    public const COLUMN_LABELS = [
        'first_name' => 'First name',
        'surname' => 'Surname',
        'telephone_1' => 'Tel 1',
        'telephone_2' => 'Tel 2',
        'email_address' => 'Email',
        'company_name' => 'Company',
        'physical_address' => 'Address',
        'created_at' => 'Created',
    ];

    /** @var list<string> */
    public const SORTABLE_COLUMNS = [
        'first_name',
        'surname',
        'telephone_1',
        'telephone_2',
        'email_address',
        'company_name',
        'physical_address',
        'created_at',
    ];

    public const DEFAULT_SORT = 'created_at';

    public const DEFAULT_DIR = 'desc';

    public static function sortColumn(?string $sort): string
    {
        if (is_string($sort) && in_array($sort, self::SORTABLE_COLUMNS, true)) {
            return $sort;
        }

        return self::DEFAULT_SORT;
    }

    public static function sortDir(?string $dir): string
    {
        $dir = strtolower((string) $dir);

        return in_array($dir, ['asc', 'desc'], true) ? $dir : self::DEFAULT_DIR;
    }

    /**
     * @param  Builder<\App\Models\Contact>  $query
     */
    public static function applySort(Builder $query, string $sort, string $dir): void
    {
        $query->orderBy($sort, $dir);

        if ($sort !== 'id') {
            $query->orderBy('id', $dir);
        }
    }

    public static function sortUrl(
        string $column,
        string $currentSort,
        string $currentDir,
        string $term = '',
    ): string {
        $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';

        return route('contacts.index', array_filter([
            'q' => trim($term) !== '' ? trim($term) : null,
            'sort' => $column,
            'dir' => $nextDir,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public static function sortIndicator(string $column, string $currentSort, string $currentDir): string
    {
        if ($column !== $currentSort) {
            return '';
        }

        return $currentDir === 'asc' ? ' ▲' : ' ▼';
    }
}
