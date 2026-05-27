<?php

namespace App\Support;

use App\Models\ImeiSaleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class CashDevicesSaleTypeFilter
{
    public const ALL = 'ALL';

    public const NONE_SALE_TYPE = 'None';

    public const SOLD_STATUS = 'Sold';

    public static function selected(Request $request): string
    {
        $saleType = $request->input('sale_type', self::ALL);

        if (! is_string($saleType) || $saleType === '') {
            return self::ALL;
        }

        if ($saleType === self::ALL) {
            return self::ALL;
        }

        if (self::selectableSaleTypes()->contains('sale_type', $saleType)) {
            return $saleType;
        }

        return self::ALL;
    }

    /**
     * @param  Builder<\App\Models\Imei>  $query
     */
    public static function applySoldExclusion(Builder $query): void
    {
        $query->where('status', '!=', self::SOLD_STATUS);
    }

    /**
     * @param  Builder<\App\Models\Imei>  $query
     */
    public static function applyToQuery(Builder $query, string $selectedSaleType): void
    {
        if ($selectedSaleType === self::ALL) {
            $saleTypes = self::selectableSaleTypes()->pluck('sale_type')->all();

            if ($saleTypes === []) {
                $query->whereRaw('0 = 1');

                return;
            }

            $query->whereIn('cash_stock_type', $saleTypes);

            return;
        }

        $query->where('cash_stock_type', $selectedSaleType);
    }

    /**
     * @return Collection<int, ImeiSaleType>
     */
    public static function selectableSaleTypes(): Collection
    {
        return ImeiSaleType::query()
            ->where('sale_type', '!=', self::NONE_SALE_TYPE)
            ->orderBy('sale_type')
            ->get();
    }
}
