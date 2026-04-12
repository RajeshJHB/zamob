<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImeiModel extends Model
{
    /** @use HasFactory<\Database\Factories\ImeiModelFactory> */
    use HasFactory;

    protected $table = 'imei_models';

    public $timestamps = false;

    protected $fillable = [
        'model',
        'serial',
        'make',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_added' => 'datetime',
        ];
    }
}
