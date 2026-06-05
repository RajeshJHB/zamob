<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory;

    protected $fillable = [
        'company_name',
        'first_name',
        'surname',
        'telephone_1',
        'telephone_2',
        'email_address',
        'physical_address',
        'related_contact_id',
        'contact_category_id',
    ];

    public function contactCategory(): BelongsTo
    {
        return $this->belongsTo(ContactCategory::class);
    }

    public function relatedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'related_contact_id');
    }

    public function serviceNotes(): HasMany
    {
        return $this->hasMany(ServiceNote::class);
    }

    public function displayName(): string
    {
        $parts = array_filter([
            trim($this->company_name),
            trim($this->first_name.' '.$this->surname),
            trim($this->telephone_1),
        ]);

        return $parts !== [] ? implode(' — ', array_unique($parts)) : 'Contact #'.$this->id;
    }

    /**
     * @param  Builder<Contact>  $query
     */
    public function scopeSearchTerm(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('company_name', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('surname', 'like', $like)
                ->orWhere('telephone_1', 'like', $like)
                ->orWhere('telephone_2', 'like', $like)
                ->orWhere('email_address', 'like', $like)
                ->orWhere('physical_address', 'like', $like);
        });
    }
}
