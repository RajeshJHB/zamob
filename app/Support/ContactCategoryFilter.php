<?php

namespace App\Support;

use App\Models\ContactCategory;
use Illuminate\Database\Eloquent\Builder;

final class ContactCategoryFilter
{
    public const ALL = 'all';

    /**
     * @return 'all'|int
     */
    public static function resolveSelected(mixed $categoryParam): string|int
    {
        if ($categoryParam === self::ALL) {
            return self::ALL;
        }

        if (is_string($categoryParam) && $categoryParam !== '' && ctype_digit($categoryParam)) {
            $categoryId = (int) $categoryParam;

            if (ContactCategory::query()->whereKey($categoryId)->exists()) {
                return $categoryId;
            }
        }

        return ContactCategory::customerId() ?? self::ALL;
    }

    /**
     * @param  Builder<\App\Models\Contact>  $query
     */
    public static function applyFilter(Builder $query, string|int $selected): void
    {
        if ($selected === self::ALL) {
            return;
        }

        $query->where('contact_category_id', $selected);
    }

    /**
     * @param  'all'|int  $selected
     */
    public static function urlParam(string|int $selected): ?string
    {
        if ($selected === self::ALL) {
            return self::ALL;
        }

        $customerId = ContactCategory::customerId();
        if ($customerId !== null && $selected === $customerId) {
            return null;
        }

        return (string) $selected;
    }
}
