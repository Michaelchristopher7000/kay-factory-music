<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class RoyaltyStatement extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ISSUED,
        self::STATUS_PAID,
        self::STATUS_VOID,
    ];

    protected $fillable = [
        'statement_code',
        'artist_id',
        'period_start',
        'period_end',
        'status',
        'currency',
        'royalty_rate',
        'total_revenue',
        'total_royalty',
        'total_paid',
        'balance',
        'issued_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'issued_at' => 'date',
            'royalty_rate' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'total_royalty' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RoyaltyStatement $statement) {
            if (empty($statement->statement_code)) {
                $statement->statement_code = static::generateNextStatementCode();
            }
        });
    }

    public static function generateNextStatementCode(): string
    {
        $latestId = DB::table('royalty_statements')->max('id') ?? 0;
        $nextNumber = $latestId + 1;
        $code = sprintf('KFM-STM-%04d', $nextNumber);

        while (DB::table('royalty_statements')->where('statement_code', $code)->exists()) {
            $nextNumber++;
            $code = sprintf('KFM-STM-%04d', $nextNumber);
        }

        return $code;
    }

    public static function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function getAuditLabel(): string
    {
        return $this->statement_code;
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RoyaltyStatementLine::class, 'statement_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RoyaltyPayment::class, 'statement_id');
    }

    public function generateLines(?int $contractId = null): void
    {
        RoyaltyStatementLine::where('statement_id', $this->id)->forceDelete();

        $entries = RevenueEntry::query()
            ->where('artist_id', $this->artist_id)
            ->where('currency', $this->currency)
            ->whereDate('period_end', '>=', $this->period_start)
            ->whereDate('period_end', '<=', $this->period_end)
            ->whereDoesntHave('royaltyStatementLines', function ($q) {
                $q->whereHas('statement', function ($sq) {
                    $sq->where('status', '!=', RoyaltyStatement::STATUS_VOID);
                });
            })
            ->get();

        foreach ($entries as $entry) {
            $revenueAmount = (float) $entry->amount;
            $rate = (float) $this->royalty_rate;
            $royaltyAmount = round($revenueAmount * $rate / 100, 2);

            RoyaltyStatementLine::create([
                'statement_id' => $this->id,
                'revenue_entry_id' => $entry->id,
                'contract_id' => $contractId,
                'release_id' => $entry->release_id,
                'track_id' => $entry->track_id,
                'source' => $entry->source,
                'description' => null,
                'revenue_amount' => $revenueAmount,
                'royalty_rate' => $rate,
                'royalty_amount' => $royaltyAmount,
            ]);
        }

        $this->recomputeTotals();
    }

    public function recomputeTotals(): void
    {
        $lineSums = RoyaltyStatementLine::where('statement_id', $this->id)
            ->selectRaw('COALESCE(SUM(revenue_amount), 0) as rev, COALESCE(SUM(royalty_amount), 0) as roy')
            ->first();

        $totalRevenue = (float) ($lineSums->rev ?? 0);
        $totalRoyalty = (float) ($lineSums->roy ?? 0);

        $totalPaid = (float) RoyaltyPayment::where('statement_id', $this->id)->sum('amount');

        $balance = round($totalRoyalty - $totalPaid, 2);

        $updates = [
            'total_revenue' => $totalRevenue,
            'total_royalty' => $totalRoyalty,
            'total_paid' => $totalPaid,
            'balance' => $balance,
        ];

        if ($this->status === self::STATUS_ISSUED && $totalPaid >= $totalRoyalty && $totalRoyalty > 0) {
            $updates['status'] = self::STATUS_PAID;
        } elseif ($this->status === self::STATUS_PAID && $totalPaid < $totalRoyalty) {
            $updates['status'] = self::STATUS_ISSUED;
        }

        $this->update($updates);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! empty($term)) {
            $op = static::likeOperator();

            $query->where(function ($q) use ($term, $op) {
                $q->where('statement_code', $op, "%{$term}%")
                  ->orWhere('notes', $op, "%{$term}%");
            });
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

    public function scopeStatus($query, ?string $status)
    {
        if (! empty($status)) {
            $query->where('status', $status);
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