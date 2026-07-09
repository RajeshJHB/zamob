<?php

namespace App\Support;

use App\Models\ImeiMake;
use App\Models\ImeiModel;
use Illuminate\Database\Eloquent\Builder;

final class ImeiReferenceText
{
    public static function normalize(?string $value): string
    {
        return trim((string) $value);
    }

    public static function equals(?string $left, ?string $right): bool
    {
        return self::normalize($left) === self::normalize($right);
    }

    public static function makeExists(string $make): bool
    {
        return self::applyWhereMake(ImeiMake::query(), $make)->exists();
    }

    public static function modelExistsForMake(string $make, string $model): bool
    {
        return self::applyWhereMakeAndModel(ImeiModel::query(), $make, $model)->exists();
    }

    public static function resolveStoredMake(string $make): ?string
    {
        return self::applyWhereMake(ImeiMake::query(), $make)->value('make');
    }

    /**
     * @param  Builder<ImeiMake>  $query
     * @return Builder<ImeiMake>
     */
    public static function applyWhereMake(Builder $query, string $make): Builder
    {
        return $query->whereRaw('TRIM(make) = ?', [self::normalize($make)]);
    }

    /**
     * @param  Builder<ImeiModel>  $query
     * @return Builder<ImeiModel>
     */
    public static function applyWhereMakeAndModel(Builder $query, string $make, string $model): Builder
    {
        return $query
            ->whereRaw('TRIM(make) = ?', [self::normalize($make)])
            ->whereRaw('TRIM(model) = ?', [self::normalize($model)]);
    }
}
