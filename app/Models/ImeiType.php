<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImeiType extends Model
{
    /** @use HasFactory<\Database\Factories\ImeiTypeFactory> */
    use HasFactory;

    protected $table = 'imei_types';

    public $timestamps = false;

    protected $fillable = [
        'type',
    ];
}
