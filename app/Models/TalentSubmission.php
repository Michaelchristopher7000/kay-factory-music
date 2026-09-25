<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TalentSubmission extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWING,
        self::STATUS_SHORTLISTED,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
    ];

    public const CATEGORIES = [
        'Music Artist',
        'Singer',
        'Rapper',
        'Producer',
        'Songwriter',
        'DJ',
        'Dancer',
        'Other',
    ];

    protected $fillable = [
        'reference_number',
        'full_name',
        'email',
        'phone',
        'location',
        'talent_category',
        'bio',
        'message',
        'social_links',
        'audio_path',
        'video_path',
        'image_path',
        'status',
        'manager_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TalentSubmission $submission) {
            if (empty($submission->reference_number)) {
                $submission->reference_number = static::generateNextReference();
            }
        });
    }

    /**
     * Generate the next sequential KFM-TAL-#### reference.
     * Mirrors Artist::generateNextArtistCode().
     */
    public static function generateNextReference(): string
    {
        $latestId = DB::table('talent_submissions')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-TAL-%04d', $nextNumber);

        while (DB::table('talent_submissions')->where('reference_number', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-TAL-%04d', $nextNumber);
        }

        return $code;
    }

    /**
     * Case-insensitive LIKE operator — matches Artist and AuditLog conventions.
     */
    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'ilike'
            : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->reference_number;
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}