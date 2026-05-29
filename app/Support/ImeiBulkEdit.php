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
     * Text fields that support partial search-text remove or replace.
     *
     * @var list<string>
     */
    public const TEXT_PARTIAL_EDIT_FIELD_KEYS = [
        self::FIELD_DEAL_DETAILS,
        self::FIELD_CUSTOMER_DETAILS,
    ];

    /** @deprecated Use TEXT_PARTIAL_EDIT_FIELD_KEYS */
    public const REMOVE_SEARCH_TEXT_FIELD_KEYS = self::TEXT_PARTIAL_EDIT_FIELD_KEYS;

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
     * @param  list<string>  $excludeFieldKeys
     * @return array<string, mixed>
     */
    public static function buildUpdateAttributes(array $replace, \DateTimeInterface $now, array $excludeFieldKeys = []): array
    {
        $attributes = [
            'date_updated' => $now,
        ];

        foreach (self::FIELD_KEYS as $fieldKey) {
            if (in_array($fieldKey, $excludeFieldKeys, true)) {
                continue;
            }

            $value = self::normalizedReplaceValue($replace, $fieldKey);
            if ($value === null) {
                continue;
            }

            $attributes[self::databaseColumn($fieldKey)] = $value;
        }

        return $attributes;
    }

    public static function removeSearchTextRequestKey(string $fieldKey): string
    {
        return 'remove_search_'.$fieldKey;
    }

    public static function replaceSearchTextRequestKey(string $fieldKey): string
    {
        return 'replace_search_text_'.$fieldKey;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function shouldRemoveSearchText(array $values, string $fieldKey): bool
    {
        if (! in_array($fieldKey, self::TEXT_PARTIAL_EDIT_FIELD_KEYS, true)) {
            return false;
        }

        if (! self::booleanValue($values[self::removeSearchTextRequestKey($fieldKey)] ?? false)) {
            return false;
        }

        return self::normalizedSearchValue($values, $fieldKey) !== null
            && self::normalizedReplaceValue($values, $fieldKey) === null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function shouldReplaceSearchText(array $values, string $fieldKey): bool
    {
        if (! in_array($fieldKey, self::TEXT_PARTIAL_EDIT_FIELD_KEYS, true)) {
            return false;
        }

        if (! self::booleanValue($values[self::replaceSearchTextRequestKey($fieldKey)] ?? false)) {
            return false;
        }

        return self::normalizedSearchValue($values, $fieldKey) !== null
            && self::normalizedReplaceValue($values, $fieldKey) !== null;
    }

    public static function replaceSearchTextInField(string $value, string $searchTerm, string $replaceWith): string
    {
        if ($searchTerm === '') {
            return trim($value);
        }

        $pattern = '/'.preg_quote($searchTerm, '/').'/iu';

        return preg_replace($pattern, $replaceWith, $value) ?? $value;
    }

    public static function removeSearchTextFromField(string $value, string $searchTerm): string
    {
        if ($searchTerm === '') {
            return trim($value);
        }

        $pattern = '/'.preg_quote($searchTerm, '/').'/iu';
        $result = preg_replace($pattern, '', $value) ?? $value;
        $result = preg_replace('/\s+/u', ' ', $result) ?? $result;

        return trim($result);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function hasAnyReplaceAction(array $values): bool
    {
        foreach (self::TEXT_PARTIAL_EDIT_FIELD_KEYS as $fieldKey) {
            if (self::shouldRemoveSearchText($values, $fieldKey)) {
                return true;
            }

            if (self::shouldReplaceSearchText($values, $fieldKey)) {
                return true;
            }
        }

        foreach (self::FIELD_KEYS as $fieldKey) {
            if (self::normalizedReplaceValue($values, $fieldKey) === null) {
                continue;
            }

            if (in_array($fieldKey, self::TEXT_PARTIAL_EDIT_FIELD_KEYS, true)
                && (self::shouldReplaceSearchText($values, $fieldKey) || self::shouldRemoveSearchText($values, $fieldKey))) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    public static function removeSearchTextFieldKeys(array $values): array
    {
        $fieldKeys = [];

        foreach (self::TEXT_PARTIAL_EDIT_FIELD_KEYS as $fieldKey) {
            if (self::shouldRemoveSearchText($values, $fieldKey)) {
                $fieldKeys[] = $fieldKey;
            }
        }

        return $fieldKeys;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    public static function replaceSearchTextFieldKeys(array $values): array
    {
        $fieldKeys = [];

        foreach (self::TEXT_PARTIAL_EDIT_FIELD_KEYS as $fieldKey) {
            if (self::shouldReplaceSearchText($values, $fieldKey)) {
                $fieldKeys[] = $fieldKey;
            }
        }

        return $fieldKeys;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    public static function partialTextEditFieldKeys(array $values): array
    {
        return array_values(array_unique([
            ...self::removeSearchTextFieldKeys($values),
            ...self::replaceSearchTextFieldKeys($values),
        ]));
    }

    private static function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
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
    public static function searchValue(array $values, string $fieldKey): ?string
    {
        return self::normalizedSearchValue($values, $fieldKey);
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

    public static function replaceValue(array $values, string $fieldKey): ?string
    {
        return self::normalizedReplaceValue($values, $fieldKey);
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
