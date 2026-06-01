<?php

namespace App\Support;

use App\Models\ImeiType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class CashDevicesTypeFilter
{
    public const ALL = 'ALL';

    public static function selectedTypeId(Request $request): ?int
    {
        $raw = $request->input('imei_type_id', self::ALL);

        if ($raw === self::ALL || $raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $id = (int) $raw;

        return ImeiType::query()->whereKey($id)->exists() ? $id : null;
    }

    public static function selected(Request $request): string
    {
        $id = self::selectedTypeId($request);
        if ($id === null) {
            return self::ALL;
        }

        $type = ImeiType::query()->whereKey($id)->value('type');

        return is_string($type) && $type !== '' ? $type : self::ALL;
    }

    /**
     * @param  Builder<\App\Models\Imei>  $query
     */
    public static function applyToQuery(Builder $query, string $selectedImeiType): void
    {
        if ($selectedImeiType === self::ALL) {
            return;
        }

        $query->where('type', $selectedImeiType);
    }

    /**
     * @return Collection<int, ImeiType>
     */
    public static function selectableTypes(): Collection
    {
        return ImeiType::query()
            ->orderBy('type')
            ->get();
    }
}
