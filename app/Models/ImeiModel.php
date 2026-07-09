<?php

namespace App\Models;

use App\Support\ImeiReferenceText;
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
        'item_code',
    ];

    protected static function booted(): void
    {
        static::saving(function (ImeiModel $imeiModel): void {
            $imeiModel->make = ImeiReferenceText::normalize($imeiModel->make);
            $imeiModel->model = ImeiReferenceText::normalize($imeiModel->model);
            $imeiModel->serial = ImeiReferenceText::normalize($imeiModel->serial);
            if ($imeiModel->item_code !== null) {
                $imeiModel->item_code = ImeiReferenceText::normalize($imeiModel->item_code);
            }
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
