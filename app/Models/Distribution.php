<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Distribution extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const PLATFORMS = [
        'spotify',
        'apple_music',
        'youtube_music',
        'amazon_music',
        'deezer',
        'tidal',
        'audiomack',
        'boomplay',
        'soundcloud',
        'pandora',
        'other',
    ];

    public const STATUSES = [
        'pending',
        'submitted',
        'live',
        'takedown',
        'rejected',
        'failed',
    ];

    protected $fillable = [
        'distribution_code',
        'release_id',
        'platform',
        'distributor',
        'status',
        'territory',
        'scheduled_for',
        'submitted_at',
        'live_at',
        'takedown_at',
        'platform_release_id',
        'platform_url',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'submitted_at' => 'date',
            'live_at' => 'date',
            'takedown_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Distribution $distribution) {
            if (empty($distribution->distribution_code)) {
                $distribution->distribution_code = static::generateNextDistributionCode();
            }
        });
    }

    public static function generateNextDistributionCode(): string
    {
        $latestId = DB::table('distributions')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-DST-%04d', $nextNumber);

        while (DB::table('distributions')->where('distribution_code', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-DST-%04d', $nextNumber);
        }

        return $code;
    }

    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->distribution_code . ' - ' . $this->platform;
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revenueEntries(): HasMany
    {
        return $this->hasMany(RevenueEntry::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! empty($term)) {
            $op = static::likeOperator();

            $query->where(function ($q) use ($term, $op) {
                $q->where('distribution_code', $op, "%{$term}%")
                  ->orWhere('distributor', $op, "%{$term}%")
                  ->orWhere('platform_release_id', $op, "%{$term}%");
            });
        }

        return $query;
    }

    public function scopeForRelease($query, ?int $releaseId)
    {
        if (! empty($releaseId)) {
            $query->where('release_id', $releaseId);
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

    public function scopeStatus($query, ?string $status)
    {
        if (! empty($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function scopeDistributor($query, ?string $distributor)
    {
        if (! empty($distributor)) {
            $op = static::likeOperator();
            $query->where('distributor', $op, "%{$distributor}%");
        }

        return $query;
    }
}