<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Artist extends Model
{
    use HasFactory, SoftDeletes, Auditable, HasSlug;

    public const STATUS_IN_TALKS = 'in_talks';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FORMER = 'former';

    public const STATUSES = [
        self::STATUS_IN_TALKS,
        self::STATUS_SIGNED,
        self::STATUS_INACTIVE,
        self::STATUS_FORMER,
    ];

    protected $fillable = [
        'artist_code',
        'slug',
        'name',
        'real_name',
        'email',
        'phone',
        'bio',
        'avatar',
        'genre',
        'country',
        'city',
        'status',
        'manager_id',
        'created_by',
        'social_links',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Artist $artist) {
            if (empty($artist->artist_code)) {
                $artist->artist_code = static::generateNextArtistCode();
            }
        });
    }

    public static function generateNextArtistCode(): string
    {
        $latestId = DB::table('artists')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-ART-%04d', $nextNumber);

        while (DB::table('artists')->where('artist_code', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-ART-%04d', $nextNumber);
        }

        return $code;
    }

    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->artist_code . ' - ' . $this->name;
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function revenueEntries(): HasMany
    {
        return $this->hasMany(RevenueEntry::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function royaltyStatements(): HasMany
    {
        return $this->hasMany(RoyaltyStatement::class);
    }

    public function royaltyPayments(): HasMany
    {
        return $this->hasMany(RoyaltyPayment::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ArtistVideo::class);
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(ArtistGalleryImage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ArtistEvent::class);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeSearch($query, ?string $term)
    {
        if (! empty($term)) {
            $op = static::likeOperator();

            $query->where(function ($q) use ($term, $op) {
                $q->where('name', $op, "%{$term}%")
                  ->orWhere('real_name', $op, "%{$term}%")
                  ->orWhere('artist_code', $op, "%{$term}%")
                  ->orWhere('email', $op, "%{$term}%");
            });
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

    public function scopeGenre($query, ?string $genre)
    {
        if (! empty($genre)) {
            $op = static::likeOperator();
            $query->where('genre', $op, "%{$genre}%");
        }

        return $query;
    }

    public function scopeManagedBy($query, ?int $managerId)
    {
        if (! empty($managerId)) {
            $query->where('manager_id', $managerId);
        }

        return $query;
    }

    public function scopePublicVisible($query)
    {
        return $query->where('status', self::STATUS_SIGNED);
    }
}