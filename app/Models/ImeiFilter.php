<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImeiFilter extends Model
{
    use HasFactory;

    protected $table = 'imeifilter';

    protected $fillable = [
        'user_id',
        'name',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
