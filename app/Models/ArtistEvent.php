<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtistEvent extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'artist_id',
        'title',
        'description',
        'venue',
        'city',
        'country',
        'event_date',
        'event_time',
        'ticket_url',
        'event_url',
        'image_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Events happening in the future.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now())
                     ->orderBy('event_date');
    }

    /**
     * Events that already happened.
     */
    public function scopePast($query)
    {
        return $query->where('event_date', '<', now())
                     ->orderByDesc('event_date');
    }
}