<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imei extends Model
{
    protected $table = 'IMEI';

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
}
