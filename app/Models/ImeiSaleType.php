<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImeiSaleType extends Model
{
    /** @use HasFactory<\Database\Factories\ImeiSaleTypeFactory> */
    use HasFactory;

    protected $table = 'imei_sale_types';

    public $timestamps = false;

    protected $fillable = [
        'sale_type',
    ];
}
