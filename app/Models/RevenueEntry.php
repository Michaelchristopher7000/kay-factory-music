<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class RevenueEntry extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const SOURCES = [
        'streaming',
        'sync',
        'physical',
        'youtube_content_id',
        'publishing',
        'merch',
        'other',
    ];

    protected $fillable = [
        'entry_code',
        'source',
        'platform',
        'artist_id',
        'release_id',
        'track_id',
        'distribution_id',
        'amount',
        'currency',
        'period_start',
        'period_end',
        'received_at',
        'reference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'received_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RevenueEntry $entry) {
            if (empty($entry->entry_code)) {
                $entry->entry_code = static::generateNextEntryCode();
            }
        });
    }

    public static function generateNextEntryCode(): string
    {
        $latestId = DB::table('revenue_entries')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-REV-%04d', $nextNumber);

        while (DB::table('revenue_entries')->where('entry_code', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-REV-%04d', $nextNumber);
        }

        return $code;
    }

    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->entry_code . ' - ' . $this->source;
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
                $q->where('entry_code', $op, "%{$term}%")
                  ->orWhere('reference', $op, "%{$term}%")
                  ->orWhere('notes', $op, "%{$term}%");
            });
        }

        return $query;
    }

    public function scopeSource($query, ?string $source)
    {
        if (! empty($source)) {
            $query->where('source', $source);
        }

        return $query;
    }

    public function scopePlatform($query, ?string $platform)
    {
        if (! empty($platform)) {
            $query->where('platform', $platform);
        }

        return $query;
    }

    public function scopeForArtist($query, $artistId)
    {
        if (! empty($artistId)) {
            $query->where('artist_id', $artistId);
        }

        return $query;
    }

    public function scopeForRelease($query, $releaseId)
    {
        if (! empty($releaseId)) {
            $query->where('release_id', $releaseId);
        }

        return $query;
    }

    public function scopeForDistribution($query, $distributionId)
    {
        if (! empty($distributionId)) {
            $query->where('distribution_id', $distributionId);
        }

        return $query;
    }

    public function scopeCurrency($query, ?string $currency)
    {
        if (! empty($currency)) {
            $query->where('currency', strtoupper($currency));
        }

        return $query;
    }

    public function scopeFromDate($query, $from)
    {
        if (! empty($from)) {
            $query->whereDate('period_end', '>=', $from);
        }

        return $query;
    }

    public function scopeToDate($query, $to)
    {
        if (! empty($to)) {
            $query->whereDate('period_end', '<=', $to);
        }

        return $query;
    }
}