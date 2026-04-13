<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImeiStatus extends Model
{
    /** @use HasFactory<\Database\Factories\ImeiStatusFactory> */
    use HasFactory;

    protected $table = 'imei_statuses';

    public $timestamps = false;

    protected $fillable = [
        'status',
    ];
}
