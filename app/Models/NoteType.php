<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoteType extends Model
{
    /** @use HasFactory<\Database\Factories\NoteTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function serviceNotes(): HasMany
    {
        return $this->hasMany(ServiceNote::class);
    }
}
