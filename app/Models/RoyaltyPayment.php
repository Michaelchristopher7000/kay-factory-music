<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class RoyaltyPayment extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CASH = 'cash';
    public const METHOD_CHEQUE = 'cheque';
    public const METHOD_OTHER = 'other';

    public const METHODS = [
        self::METHOD_BANK_TRANSFER,
        self::METHOD_CASH,
        self::METHOD_CHEQUE,
        self::METHOD_OTHER,
    ];

    protected $fillable = [
        'payment_code',
        'statement_id',
        'artist_id',
        'amount',
        'currency',
        'paid_at',
        'method',
        'reference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RoyaltyPayment $payment) {
            if (empty($payment->payment_code)) {
                $payment->payment_code = static::generateNextPaymentCode();
            }
        });
    }

    public static function generateNextPaymentCode(): string
    {
        $latestId = DB::table('royalty_payments')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-PAY-%04d', $nextNumber);

        while (DB::table('royalty_payments')->where('payment_code', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-PAY-%04d', $nextNumber);
        }

        return $code;
    }

    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->payment_code;
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(RoyaltyStatement::class, 'statement_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! empty($term)) {
            $op = static::likeOperator();

            $query->where(function ($q) use ($term, $op) {
                $q->where('payment_code', $op, "%{$term}%")
                  ->orWhere('reference', $op, "%{$term}%")
                  ->orWhere('notes', $op, "%{$term}%");
            });
        }

        return $query;
    }

    public function scopeForStatement($query, $statementId)
    {
        if (! empty($statementId)) {
            $query->where('statement_id', $statementId);
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

    public function scopeMethod($query, ?string $method)
    {
        if (! empty($method)) {
            $query->where('method', $method);
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
            $query->whereDate('paid_at', '>=', $from);
        }

        return $query;
    }

    public function scopeToDate($query, $to)
    {
        if (! empty($to)) {
            $query->whereDate('paid_at', '<=', $to);
        }

        return $query;
    }
}