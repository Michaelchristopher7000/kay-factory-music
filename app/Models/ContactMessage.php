<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ContactMessage extends Model
{
    use HasFactory;

    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ = 'read';
    public const STATUS_REPLIED = 'replied';
    public const STATUS_RESOLVED = 'resolved';

    public const STATUSES = [
        self::STATUS_UNREAD,
        self::STATUS_READ,
        self::STATUS_REPLIED,
        self::STATUS_RESOLVED,
    ];

    public const CATEGORIES = [
        'general' => 'General Enquiry',
        'booking' => 'Booking',
        'press' => 'Press & Media',
        'partnership' => 'Partnership',
        'demo' => 'Demo Submission',
        'other' => 'Other',
    ];

    protected $fillable = [
        'reference_number',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'category',
        'status',
        'internal_notes',
        'assigned_to',
        'read_at',
        'replied_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'replied_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ContactMessage $message) {
            if (empty($message->reference_number)) {
                $message->reference_number = static::generateNextReference();
            }
        });
    }

    /**
     * Generate the next sequential KFM-CON-#### reference.
     */
    public static function generateNextReference(): string
    {
        $latestId = DB::table('contact_messages')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-CON-%04d', $nextNumber);

        while (DB::table('contact_messages')->where('reference_number', $code)->exists()) {
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
        return $this->reference_number;
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}