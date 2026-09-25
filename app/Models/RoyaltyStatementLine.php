<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoyaltyStatementLine extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'statement_id',
        'revenue_entry_id',
        'contract_id',
        'release_id',
        'track_id',
        'source',
        'description',
        'revenue_amount',
        'royalty_rate',
        'royalty_amount',
    ];

    protected function casts(): array
    {
        return [
            'revenue_amount' => 'decimal:2',
            'royalty_rate' => 'decimal:2',
            'royalty_amount' => 'decimal:2',
        ];
    }

    public function getAuditLabel(): string
    {
        return 'Line #' . $this->id;
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(RoyaltyStatement::class, 'statement_id');
    }

    public function revenueEntry(): BelongsTo
    {
        return $this->belongsTo(RevenueEntry::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function scopeForStatement($query, $statementId)
    {
        if (! empty($statementId)) {
            $query->where('statement_id', $statementId);
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

    public function scopeForRelease($query, $releaseId)
    {
        if (! empty($releaseId)) {
            $query->where('release_id', $releaseId);
        }

        return $query;
    }

    public function scopeForTrack($query, $trackId)
    {
        if (! empty($trackId)) {
            $query->where('track_id', $trackId);
        }

        return $query;
    }
}