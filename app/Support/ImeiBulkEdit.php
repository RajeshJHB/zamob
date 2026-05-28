<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class ImeiBulkEdit
{
    public const FIELD_SALE_TYPE = 'sale_type';

    public const FIELD_STATUS = 'status';

    public const FIELD_DEAL_DETAILS = 'deal_details';

    public const FIELD_CUSTOMER_DETAILS = 'customer_details';

    /**
     * @var list<string>
     */
    public const FIELD_KEYS = [
        self::FIELD_SALE_TYPE,
        self::FIELD_STATUS,
        self::FIELD_DEAL_DETAILS,
        self::FIELD_CUSTOMER_DETAILS,
    ];

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::FIELD_SALE_TYPE => 'Sale Type',
            self::FIELD_STATUS => 'Status',
            self::FIELD_DEAL_DETAILS => 'Deal Details',
            self::FIELD_CUSTOMER_DETAILS => 'Customer Details',
        ];
    }

    public static function databaseColumn(string $fieldKey): string
    {
        return match ($fieldKey) {
            self::FIELD_SALE_TYPE => 'cash_stock_type',
            self::FIELD_STATUS => 'status',
            self::FIELD_DEAL_DETAILS => 'ref',
            self::FIELD_CUSTOMER_DETAILS => 'notes',
            default => throw new \InvalidArgumentException('Unknown bulk edit field: '.$fieldKey),
        };
    }

    /**
     * @param  array<string, mixed>  $search
     */
    public static function applySearchCriteria(Builder $query, array $search): void
    {
        foreach (self::FIELD_KEYS as $fieldKey) {
            $value = self::normalizedSearchValue($search, $fieldKey);
            if ($value === null) {
                continue;
            }

            $column = self::databaseColumn($fieldKey);

            if (self::usesContainsSearch($fieldKey)) {
                $query->where($column, 'LIKE', '%'.$value.'%');

                continue;
            }

            $query->where($column, $value);
        }
    }

    public static function usesContainsSearch(string $fieldKey): bool
    {
        return in_array($fieldKey, [self::FIELD_DEAL_DETAILS, self::FIELD_CUSTOMER_DETAILS], true);
    }

    /**
     * @param  array<string, mixed>  $replace
     * @return array<string, mixed>
     */
    public static function buildUpdateAttributes(array $replace, \DateTimeInterface $now): array
    {
        $attributes = [
            'date_updated' => $now,
        ];

        foreach (self::FIELD_KEYS as $fieldKey) {
            $value = self::normalizedReplaceValue($replace, $fieldKey);
            if ($value === null) {
                continue;
            }

            $attributes[self::databaseColumn($fieldKey)] = $value;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function hasAnySearchValue(array $values): bool
    {
        foreach (self::FIELD_KEYS as $fieldKey) {
            if (self::normalizedSearchValue($values, $fieldKey) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function hasAnyReplaceValue(array $values): bool
    {
        foreach (self::FIELD_KEYS as $fieldKey) {
            if (self::normalizedReplaceValue($values, $fieldKey) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function normalizedSearchValue(array $values, string $fieldKey): ?string
    {
        $raw = $values['search_'.$fieldKey] ?? null;
        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function normalizedReplaceValue(array $values, string $fieldKey): ?string
    {
        $raw = $values['replace_'.$fieldKey] ?? null;
        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);

        return $value === '' ? null : $value;
    }
}
