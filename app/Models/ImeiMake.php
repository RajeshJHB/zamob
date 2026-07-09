<?php

namespace App\Models;

use App\Support\ImeiReferenceText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImeiMake extends Model
{
    /** @use HasFactory<\Database\Factories\ImeiMakeFactory> */
    use HasFactory;

    protected $table = 'imei_make';

    public $timestamps = false;

    protected $fillable = [
        'make',
    ];

    protected static function booted(): void
    {
        static::saving(function (ImeiMake $imeiMake): void {
            $imeiMake->make = ImeiReferenceText::normalize($imeiMake->make);
        });
    }

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
