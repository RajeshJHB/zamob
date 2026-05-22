<?php

namespace App\Models;

use App\Support\ServiceNoteNumberAssigner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceNote extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceNoteFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'contact_id',
        'note_number',
        'note_type_id',
        'status',
        'heading',
        'noted_at',
        'body',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
        'attachment_size',
        'created_by',
        'staff',
    ];

    protected function casts(): array
    {
        return [
            'noted_at' => 'datetime',
            'contact_id' => 'integer',
            'note_number' => 'integer',
            'note_type_id' => 'integer',
            'created_by' => 'integer',
            'attachment_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ServiceNote $note): void {
            $note->noted_at ??= now();
        });

        static::created(function (ServiceNote $note): void {
            if ($note->created_at === null) {
                return;
            }

            $note->forceFill(['noted_at' => $note->created_at])->saveQuietly();
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function noteType(): BelongsTo
    {
        return $this->belongsTo(NoteType::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formattedNoteNumber(): string
    {
        return ServiceNoteNumberAssigner::format((int) $this->note_number);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null && $this->attachment_path !== '';
    }

    /**
     * @param  Builder<ServiceNote>  $query
     */
    public function scopeSearchTerm(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like, $term) {
            $q->where('heading', 'like', $like)
                ->orWhere('body', 'like', $like)
                ->orWhere('staff', 'like', $like);

            if (preg_match('/^SN-?(\d+)$/i', $term, $matches)) {
                $q->orWhere('note_number', (int) $matches[1]);
            }
        });
    }
}
