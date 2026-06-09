<?php

namespace App\Support;

use App\Models\ImeiLocation;
use App\Models\ImeiMake;
use App\Models\ImeiSaleType;
use App\Models\ImeiStatus;
use App\Models\ImeiType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ImeiFieldFilter
{
    public const FIELD_SALE_TYPE = 'sale_type';

    /** @var list<int> */
    public const FILTER_INDICES = [1, 2, 3];

    /** @var list<string> */
    public const FIELDS = [
        'location',
        'make',
        'type',
        'status',
        self::FIELD_SALE_TYPE,
    ];

    /** @var array<string, string> */
    public const FIELD_LABELS = [
        'location' => 'Location',
        'make' => 'Make',
        'type' => 'Type',
        'status' => 'Status',
        self::FIELD_SALE_TYPE => 'Sale Type',
    ];

    /**
     * @return list<string>
     */
    public static function queryKeys(): array
    {
        $keys = [];

        foreach (self::FILTER_INDICES as $index) {
            $keys[] = 'field_filter_'.$index;
            $keys[] = 'field_value_'.$index;
            $keys[] = self::excludeRequestKey($index);
        }

        return $keys;
    }

    public static function excludeRequestKey(int $index): string
    {
        return 'field_not_'.$index;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function picklistOptions(?User $user = null): array
    {
        return [
            'location' => ImeiLocation::query()->orderBy('location')->pluck('location')->all(),
            'make' => ImeiMake::query()->orderBy('make')->pluck('make')->all(),
            'type' => ImeiType::query()->orderBy('type')->pluck('type')->all(),
            'status' => ImeiDeletedStatus::appendToStatusPicklist(
                ImeiStatus::query()->orderBy('status')->pluck('status')->all(),
                $user,
            ),
            self::FIELD_SALE_TYPE => ImeiSaleType::query()->orderBy('sale_type')->pluck('sale_type')->all(),
        ];
    }

    public static function databaseColumn(string $field): string
    {
        return match ($field) {
            self::FIELD_SALE_TYPE => 'cash_stock_type',
            default => $field,
        };
    }

    public static function hasActive(Request $request): bool
    {
        foreach (self::FILTER_INDICES as $index) {
            if (self::pairFromRequest($request, $index) !== null) {
                return true;
            }
        }

        return false;
    }

    public static function statusFilterValue(Request $request): ?string
    {
        foreach (self::FILTER_INDICES as $index) {
            $pair = self::pairFromRequest($request, $index);
            if ($pair !== null && $pair['field'] === 'status' && ! $pair['exclude']) {
                return $pair['value'];
            }
        }

        return null;
    }

    /**
     * @param  Builder<\App\Models\Imei>  $query
     */
    public static function applyToQuery(Builder $query, Request $request): void
    {
        /** @var array<string, list<array{field: string, value: string, exclude: bool}>> $grouped */
        $grouped = [];

        foreach (self::FILTER_INDICES as $index) {
            $pair = self::pairFromRequest($request, $index);
            if ($pair === null) {
                continue;
            }

            $grouped[$pair['field']][] = $pair;
        }

        foreach ($grouped as $field => $fieldPairs) {
            self::applyFieldGroupToQuery($query, $field, $fieldPairs);
        }
    }

    /**
     * @param  Builder<\App\Models\Imei>  $query
     * @param  list<array{field: string, value: string, exclude: bool}>  $fieldPairs
     */
    private static function applyFieldGroupToQuery(Builder $query, string $field, array $fieldPairs): void
    {
        $column = self::databaseColumn($field);

        if (count($fieldPairs) === 1) {
            $pair = $fieldPairs[0];
            if ($pair['exclude']) {
                $query->where($column, '!=', $pair['value']);
            } else {
                $query->where($column, $pair['value']);
            }

            return;
        }

        $includes = array_values(array_filter(
            $fieldPairs,
            fn (array $pair): bool => ! $pair['exclude'],
        ));
        $excludes = array_values(array_filter(
            $fieldPairs,
            fn (array $pair): bool => $pair['exclude'],
        ));

        if ($includes !== []) {
            $query->where(function (Builder $groupedQuery) use ($column, $includes): void {
                foreach ($includes as $index => $pair) {
                    if ($index === 0) {
                        $groupedQuery->where($column, $pair['value']);
                    } else {
                        $groupedQuery->orWhere($column, $pair['value']);
                    }
                }
            });
        }

        foreach ($excludes as $pair) {
            $query->where($column, '!=', $pair['value']);
        }
    }

    /**
     * @return array{field: string, value: string, exclude: bool}|null
     */
    public static function pairFromRequest(Request $request, int $index): ?array
    {
        if (! in_array($index, self::FILTER_INDICES, true)) {
            return null;
        }

        $field = self::normalizeField($request->input('field_filter_'.$index));
        $value = trim((string) $request->input('field_value_'.$index, ''));

        if ($field === null || $value === '') {
            return null;
        }

        return [
            'field' => $field,
            'value' => $value,
            'exclude' => $request->boolean(self::excludeRequestKey($index)),
        ];
    }

    public static function normalizeField(mixed $field): ?string
    {
        if (! is_string($field)) {
            return null;
        }

        $field = trim($field);
        if ($field === '') {
            return null;
        }

        return in_array($field, self::FIELDS, true) ? $field : null;
    }
}
