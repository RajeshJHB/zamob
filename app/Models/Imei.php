<?php

namespace App\Models;

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
        'stock_take_date',
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
     * Match rows whose IMEI equals the given 15-digit string after stripping spaces and dashes.
     */
    public function scopeWhereNormalizedImei(Builder $query, string $digitsOnly): Builder
    {
        return $query->whereRaw(
            "replace(replace(replace(trim(imei), ' ', ''), '-', ''), '/', '') = ?",
            [$digitsOnly]
        );
    }
}
