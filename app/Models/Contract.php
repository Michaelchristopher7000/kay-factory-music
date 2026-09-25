<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Contract extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const TYPE_ARTIST_AGREEMENT = 'artist_agreement';
    public const TYPE_RECORDING_AGREEMENT = 'recording_agreement';
    public const TYPE_DISTRIBUTION_AGREEMENT = 'distribution_agreement';
    public const TYPE_MANAGEMENT_AGREEMENT = 'management_agreement';
    public const TYPE_PUBLISHING_AGREEMENT = 'publishing_agreement';
    public const TYPE_LICENSING_AGREEMENT = 'licensing_agreement';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_ARTIST_AGREEMENT,
        self::TYPE_RECORDING_AGREEMENT,
        self::TYPE_DISTRIBUTION_AGREEMENT,
        self::TYPE_MANAGEMENT_AGREEMENT,
        self::TYPE_PUBLISHING_AGREEMENT,
        self::TYPE_LICENSING_AGREEMENT,
        self::TYPE_OTHER,
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_SIGNATURE = 'pending_signature';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_SIGNATURE,
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
        self::STATUS_TERMINATED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'contract_code',
        'artist_id',
        'title',
        'type',
        'status',
        'start_date',
        'end_date',
        'signed_date',
        'advance_amount',
        'currency',
        'royalty_rate',
        'terms',
        'document_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_date' => 'date',
            'advance_amount' => 'decimal:2',
            'royalty_rate' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Contract $contract) {
            if (empty($contract->contract_code)) {
                $contract->contract_code = static::generateNextContractCode();
            }
        });
    }

    public static function generateNextContractCode(): string
    {
        $latestId = DB::table('contracts')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-CON-%04d', $nextNumber);

        while (DB::table('contracts')->where('contract_code', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-CON-%04d', $nextNumber);
        }

        return $code;
    }

    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->contract_code . ' - ' . $this->title;
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
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
                $q->where('title', $op, "%{$term}%")
                  ->orWhere('contract_code', $op, "%{$term}%")
                  ->orWhere('terms', $op, "%{$term}%");
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

    public function scopeType($query, ?string $type)
    {
        if (! empty($type)) {
            $query->where('type', $type);
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
}