<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Track extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'track_code',
        'artist_id',
        'title',
        'isrc',
        'duration_seconds',
        'genre',
        'language',
        'bpm',
        'key',
        'is_explicit',
        'composer',
        'writers',
        'producers',
        'featured_artists',
        'recorded_date',
        'lyrics',
        'audio_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_explicit' => 'boolean',
            'duration_seconds' => 'integer',
            'bpm' => 'integer',
            'writers' => 'array',
            'producers' => 'array',
            'featured_artists' => 'array',
            'recorded_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Track $track) {
            if (empty($track->track_code)) {
                $track->track_code = static::generateNextTrackCode();
            }
        });
    }

    public static function generateNextTrackCode(): string
    {
        $latestId = DB::table('tracks')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-TRK-%04d', $nextNumber);

        while (DB::table('tracks')->where('track_code', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-TRK-%04d', $nextNumber);
        }

        return $code;
    }

    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->track_code . ' - ' . $this->title;
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function releases(): BelongsToMany
    {
        return $this->belongsToMany(Release::class)
            ->withPivot('position')
            ->withTimestamps();
    }

    public function revenueEntries(): HasMany
    {
        return $this->hasMany(RevenueEntry::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function royaltyStatementLines(): HasMany
    {
        return $this->hasMany(RoyaltyStatementLine::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! empty($term)) {
            $op = static::likeOperator();

            $query->where(function ($q) use ($term, $op) {
                $q->where('title', $op, "%{$term}%")
                  ->orWhere('track_code', $op, "%{$term}%")
                  ->orWhere('isrc', $op, "%{$term}%");
            });
        }

        return $query;
    }

    public function scopeForArtist($query, ?int $artistId)
    {
        if (! empty($artistId)) {
            $query->where('artist_id', $artistId);
        }

        return $query;
    }

    public function scopeGenre($query, ?string $genre)
    {
        if (! empty($genre)) {
            $op = static::likeOperator();
            $query->where('genre', $op, "%{$genre}%");
        }

        return $query;
    }

    public function scopeExplicit($query, $isExplicit)
    {
        if ($isExplicit === null || $isExplicit === '') {
            return $query;
        }

        $flag = filter_var($isExplicit, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($flag !== null) {
            $query->where('is_explicit', $flag);
        }

        return $query;
    }
}