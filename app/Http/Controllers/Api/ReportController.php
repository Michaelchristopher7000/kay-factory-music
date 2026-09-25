<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Release;
use App\Models\RoyaltyStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ---------------------------------------------------------------
    // 1. Artist Summary
    // ---------------------------------------------------------------

    public function artistSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artist_id' => ['nullable', 'integer', 'exists:artists,id'],
        ]);

        $artists = Artist::query()
            ->when($validated['artist_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->withCount(['tracks', 'releases', 'contracts'])
            ->withSum('revenueEntries', 'amount')
            ->withSum('expenses', 'amount')
            ->withSum([
                'royaltyStatements as royalty_generated_sum' => function ($q) {
                    $q->where('status', '!=', RoyaltyStatement::STATUS_VOID);
                },
            ], 'total_royalty')
            ->withSum([
                'royaltyPayments as royalty_paid_sum' => function ($q) {
                    $q->whereHas('statement', function ($sq) {
                        $sq->where('status', '!=', RoyaltyStatement::STATUS_VOID);
                    });
                },
            ], 'amount')
            ->orderBy('name')
            ->get();

        $data = $artists->map(function (Artist $artist) {
            $revenue = (float) ($artist->revenue_entries_sum_amount ?? 0);
            $expenses = (float) ($artist->expenses_sum_amount ?? 0);
            $royaltyGenerated = (float) ($artist->royalty_generated_sum ?? 0);
            $royaltyPaid = (float) ($artist->royalty_paid_sum ?? 0);
            $balance = round($royaltyGenerated - $royaltyPaid, 2);

            return [
                'artist_id' => $artist->id,
                'artist_name' => $artist->name,
                'total_tracks' => (int) $artist->tracks_count,
                'total_releases' => (int) $artist->releases_count,
                'total_contracts' => (int) $artist->contracts_count,
                'total_revenue' => $this->money($revenue),
                'total_expenses' => $this->money($expenses),
                'total_royalty_generated' => $this->money($royaltyGenerated),
                'total_royalty_paid' => $this->money($royaltyPaid),
                'outstanding_royalty_balance' => $this->money($balance),
            ];
        })->values();

        return $this->respond($data, [
            'artist_id' => $validated['artist_id'] ?? null,
        ]);
    }

    // ---------------------------------------------------------------
    // 2. Release Performance
    // ---------------------------------------------------------------

    public function releasePerformance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $releases = Release::query()
            ->when($validated['from'] ?? null, fn ($q, $from) => $q->whereDate('release_date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($q, $to) => $q->whereDate('release_date', '<=', $to))
            ->with('artist:id,name')
            ->withCount(['tracks', 'distributions'])
            ->withSum('revenueEntries', 'amount')
            ->withSum('expenses', 'amount')
            ->orderByDesc('release_date')
            ->orderByDesc('id')
            ->get();

        $data = $releases->map(function (Release $release) {
            $revenue = (float) ($release->revenue_entries_sum_amount ?? 0);
            $expenses = (float) ($release->expenses_sum_amount ?? 0);
            $net = round($revenue - $expenses, 2);

            return [
                'release_id' => $release->id,
                'release_title' => $release->title,
                'artist' => $release->artist ? [
                    'id' => $release->artist->id,
                    'name' => $release->artist->name,
                ] : null,
                'type' => $release->type,
                'release_date' => $release->release_date?->toDateString(),
                'track_count' => (int) $release->tracks_count,
                'distribution_count' => (int) $release->distributions_count,
                'revenue' => $this->money($revenue),
                'expenses' => $this->money($expenses),
                'net_amount' => $this->money($net),
            ];
        })->values();

        return $this->respond($data, [
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ]);
    }

    // ---------------------------------------------------------------
    // 3. Distribution Status
    // ---------------------------------------------------------------

    public function distributionStatus(): JsonResponse
    {
        $rows = DB::table('distributions')
            ->whereNull('deleted_at')
            ->select(
                'platform',
                DB::raw('count(*) as total_distributions'),
                DB::raw("sum(case when status = 'pending' then 1 else 0 end) as pending_count"),
                DB::raw("sum(case when status = 'submitted' then 1 else 0 end) as submitted_count"),
                DB::raw("sum(case when status = 'live' then 1 else 0 end) as live_count"),
                DB::raw("sum(case when status = 'takedown' then 1 else 0 end) as takedown_count"),
                DB::raw("sum(case when status = 'rejected' then 1 else 0 end) as rejected_count"),
                DB::raw("sum(case when status = 'failed' then 1 else 0 end) as failed_count")
            )
            ->groupBy('platform')
            ->orderBy('platform')
            ->get()
            ->map(function ($row) {
                return [
                    'platform' => $row->platform,
                    'total_distributions' => (int) $row->total_distributions,
                    'pending_count' => (int) $row->pending_count,
                    'submitted_count' => (int) $row->submitted_count,
                    'live_count' => (int) $row->live_count,
                    'takedown_count' => (int) $row->takedown_count,
                    'rejected_count' => (int) $row->rejected_count,
                    'failed_count' => (int) $row->failed_count,
                ];
            });

        return $this->respond($rows, []);
    }

    // ---------------------------------------------------------------
    // 4. Revenue Breakdown
    // ---------------------------------------------------------------

    public function revenueBySource(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $currency = isset($validated['currency']) ? strtoupper($validated['currency']) : null;

        $rows = DB::table('revenue_entries')
            ->whereNull('deleted_at')
            ->when($validated['from'] ?? null, fn ($q, $from) => $q->whereDate('period_end', '>=', $from))
            ->when($validated['to'] ?? null, fn ($q, $to) => $q->whereDate('period_end', '<=', $to))
            ->when($currency, fn ($q, $c) => $q->where('currency', $c))
            ->select(
                'source',
                'currency',
                DB::raw('sum(amount) as total_amount'),
                DB::raw('count(*) as entry_count')
            )
            ->groupBy('source', 'currency')
            ->orderBy('source')
            ->orderBy('currency')
            ->get()
            ->map(function ($row) {
                return [
                    'source' => $row->source,
                    'currency' => $row->currency,
                    'total_amount' => $this->money($row->total_amount),
                    'entry_count' => (int) $row->entry_count,
                ];
            });

        return $this->respond($rows, [
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'currency' => $currency,
        ]);
    }

    // ---------------------------------------------------------------
    // 5. Expense Breakdown
    // ---------------------------------------------------------------

    public function expenseByCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $currency = isset($validated['currency']) ? strtoupper($validated['currency']) : null;

        $rows = DB::table('expenses')
            ->whereNull('deleted_at')
            ->when($validated['from'] ?? null, fn ($q, $from) => $q->whereDate('incurred_at', '>=', $from))
            ->when($validated['to'] ?? null, fn ($q, $to) => $q->whereDate('incurred_at', '<=', $to))
            ->when($currency, fn ($q, $c) => $q->where('currency', $c))
            ->select(
                'category',
                'currency',
                DB::raw('sum(amount) as total_amount'),
                DB::raw('count(*) as entry_count')
            )
            ->groupBy('category', 'currency')
            ->orderBy('category')
            ->orderBy('currency')
            ->get()
            ->map(function ($row) {
                return [
                    'category' => $row->category,
                    'currency' => $row->currency,
                    'total_amount' => $this->money($row->total_amount),
                    'entry_count' => (int) $row->entry_count,
                ];
            });

        return $this->respond($rows, [
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'currency' => $currency,
        ]);
    }

    // ---------------------------------------------------------------
    // 6. Royalty Balance
    // ---------------------------------------------------------------

    public function royaltyBalances(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $currency = isset($validated['currency']) ? strtoupper($validated['currency']) : null;

        $rows = DB::table('royalty_statements as rs')
            ->join('artists as a', 'a.id', '=', 'rs.artist_id')
            ->whereNull('rs.deleted_at')
            ->where('rs.status', '!=', RoyaltyStatement::STATUS_VOID)
            ->when($currency, fn ($q, $c) => $q->where('rs.currency', $c))
            ->select(
                'rs.artist_id',
                'a.name as artist_name',
                'rs.currency',
                DB::raw('sum(rs.total_royalty) as total_royalty'),
                DB::raw('sum(rs.total_paid) as total_paid'),
                DB::raw('sum(rs.balance) as outstanding_balance')
            )
            ->groupBy('rs.artist_id', 'a.name', 'rs.currency')
            ->orderBy('a.name')
            ->orderBy('rs.currency')
            ->get()
            ->map(function ($row) {
                return [
                    'artist_id' => (int) $row->artist_id,
                    'artist_name' => $row->artist_name,
                    'currency' => $row->currency,
                    'total_royalty' => $this->money($row->total_royalty),
                    'total_paid' => $this->money($row->total_paid),
                    'outstanding_balance' => $this->money($row->outstanding_balance),
                ];
            });

        return $this->respond($rows, [
            'currency' => $currency,
        ]);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function respond($data, array $filters = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'filters' => $filters,
            ],
        ]);
    }

    protected function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}