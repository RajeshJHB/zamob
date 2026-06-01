<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class CashDevicesTable
{
    /** @var list<string> */
    public const SORTABLE_COLUMNS = [
        'date_in',
        'make',
        'model',
        'ref',
        'imei',
        'selling_price',
    ];

    public const DEFAULT_SORT = 'date_in';

    public const DEFAULT_DIR = 'desc';

    public const RETURN_TO = 'dashboard';

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
     * @param  Builder<\App\Models\Imei>  $query
     */
    public static function applySort(Builder $query, string $sort, string $dir): void
    {
        $query->orderBy($sort, $dir);

        if ($sort !== 'id') {
            $query->orderBy('id', $dir);
        }
    }

    public static function returnQuery(
        string $sort,
        string $dir,
        string $saleType = CashDevicesSaleTypeFilter::ALL,
        ?int $imeiTypeId = null,
    ): string {
        return http_build_query(array_filter([
            'return_to' => self::RETURN_TO,
            'sort' => $sort,
            'dir' => $dir,
            'sale_type' => $saleType !== CashDevicesSaleTypeFilter::ALL ? $saleType : null,
            'imei_type_id' => $imeiTypeId,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public static function sortUrl(
        string $column,
        string $currentSort,
        string $currentDir,
        string $saleType = CashDevicesSaleTypeFilter::ALL,
        ?int $imeiTypeId = null,
    ): string {
        $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';

        return route('dashboard', array_filter([
            'sort' => $column,
            'dir' => $nextDir,
            'sale_type' => $saleType !== CashDevicesSaleTypeFilter::ALL ? $saleType : null,
            'imei_type_id' => $imeiTypeId,
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
