<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImeiLocation extends Model
{
    /** @use HasFactory<\Database\Factories\ImeiLocationFactory> */
    use HasFactory;

    protected $table = 'imei_locations';

    public $timestamps = false;

    protected $fillable = [
        'location',
    ];
}
