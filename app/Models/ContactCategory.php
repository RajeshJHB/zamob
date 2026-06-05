<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactCategory extends Model
{
    /** @use HasFactory<\Database\Factories\ContactCategoryFactory> */
    use HasFactory;

    public const CUSTOMER_NAME = 'Customer';

    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public static function customerId(): ?int
    {
        static $customerId = null;

        if ($customerId === null) {
            $id = self::query()->where('name', self::CUSTOMER_NAME)->value('id');
            $customerId = $id !== null ? (int) $id : false;
        }

        return $customerId === false ? null : $customerId;
    }
}
