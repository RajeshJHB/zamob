<?php

namespace App\Support;

use App\Models\ImeiLocation;
use App\Models\ImeiMake;
use App\Models\ImeiStatus;
use App\Models\ImeiType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ImeiFieldFilter
{
    /** @var list<string> */
    public const FIELDS = [
        'location',
        'make',
        'type',
        'status',
    ];

    /** @var array<string, string> */
    public const FIELD_LABELS = [
        'location' => 'Location',
        'make' => 'Make',
        'type' => 'Type',
        'status' => 'Status',
    ];

    /**
     * @return list<string>
     */
    public static function queryKeys(): array
    {
        return [
            'field_filter_1',
            'field_value_1',
            'field_filter_2',
            'field_value_2',
        ];
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
        ];
    }

    public static function hasActive(Request $request): bool
    {
        foreach ([1, 2] as $index) {
            if (self::pairFromRequest($request, $index) !== null) {
                return true;
            }
        }

        return false;
    }

    public static function statusFilterValue(Request $request): ?string
    {
        foreach ([1, 2] as $index) {
            $pair = self::pairFromRequest($request, $index);
            if ($pair !== null && $pair['field'] === 'status') {
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
        foreach ([1, 2] as $index) {
            $pair = self::pairFromRequest($request, $index);
            if ($pair === null) {
                continue;
            }

            $query->where($pair['field'], $pair['value']);
        }
    }

    /**
     * @return array{field: string, value: string}|null
     */
    public static function pairFromRequest(Request $request, int $index): ?array
    {
        if (! in_array($index, [1, 2], true)) {
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
