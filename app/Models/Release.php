<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Release extends Model
{
    use HasFactory, SoftDeletes, Auditable, HasSlug;

    public const TYPE_SINGLE = 'single';
    public const TYPE_EP = 'ep';
    public const TYPE_ALBUM = 'album';
    public const TYPE_MIXTAPE = 'mixtape';
    public const TYPE_COMPILATION = 'compilation';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_SINGLE,
        self::TYPE_EP,
        self::TYPE_ALBUM,
        self::TYPE_MIXTAPE,
        self::TYPE_COMPILATION,
        self::TYPE_OTHER,
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RELEASED = 'released';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SCHEDULED,
        self::STATUS_RELEASED,
        self::STATUS_ARCHIVED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'release_code',
        'slug',
        'artist_id',
        'title',
        'type',
        'status',
        'release_date',
        'pre_save_date',
        'upc',
        'description',
        'cover_art_path',
        'label_copy',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'pre_save_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Release $release) {
            if (empty($release->release_code)) {
                $release->release_code = static::generateNextReleaseCode();
            }
        });
    }

    public static function generateNextReleaseCode(): string
    {
        $latestId = DB::table('releases')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-REL-%04d', $nextNumber);

        while (DB::table('releases')->where('release_code', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-REL-%04d', $nextNumber);
        }

        return $code;
    }

    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->release_code . ' - ' . $this->title;
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class)
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('release_track.position');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
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
                  ->orWhere('release_code', $op, "%{$term}%")
                  ->orWhere('upc', $op, "%{$term}%");
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

    public function scopeType($query, ?string $type)
    {
        if (! empty($type)) {
            $query->where('type', $type);
        }

        return $query;
    }

    public function scopeStatus($query, ?string $status)
    {
        if (! empty($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function scopeForYear($query, $year)
    {
        if (! empty($year) && is_numeric($year)) {
            $query->whereYear('release_date', (int) $year);
        }

        return $query;
    }

    public function scopePublicVisible($query)
    {
        return $query->where('status', self::STATUS_RELEASED);
    }
}