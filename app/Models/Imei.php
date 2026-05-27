<?php

namespace App\Models;

use App\Support\ImeiDeletedStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Imei extends Model
{
    protected $table = 'imei';

    /**
     * The legacy `imei` table uses `date_updated` instead of Laravel's `updated_at` / `created_at`.
     */
    public $timestamps = false;

    protected $fillable = [
        'date_in',
        'cash_stock_type',
        'date_updated',
        'make',
        'model',
        'sn',
        'imei',
        'location',
        'type',
        'status',
        'notes',
        'phonenumber',
        'ref',
        'staff',
        'item_code',
        'ourON',
        'salesON',
        'cost_excl',
        'selling_price',
    ];

    protected function casts(): array
    {
        return [
            'date_in' => 'datetime',
            'date_updated' => 'datetime',
            'selling_price' => 'integer',
        ];
    }

    /**
     * Match rows whose IMEI equals the given value after trim and removing spaces, dashes, and slashes (same as {@see \App\Support\ImeiValidator::normalizeNonStandard()}).
     */
    public function scopeWhereNormalizedImei(Builder $query, string $normalizedKey): Builder
    {
        return $query->whereRaw(
            "replace(replace(replace(trim(imei), ' ', ''), '-', ''), '/', '') = ?",
            [$normalizedKey]
        );
    }

    /**
     * @param  Builder<Imei>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        ImeiDeletedStatus::applyVisibleScope($query, $user);

        return $query;
    }
}
