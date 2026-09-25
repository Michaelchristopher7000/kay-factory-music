<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTalentSubmissionRequest;
use App\Http\Resources\TalentSubmissionResource;
use App\Models\AuditLog;
use App\Models\TalentSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TalentSubmissionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $submissions = TalentSubmission::query()
            ->with('reviewer:id,name,email')
            ->when($request->input('search'), function ($q, $term) {
                $op = TalentSubmission::likeOperator();
                $q->where(function ($q) use ($term, $op) {
                    $q->where('full_name', $op, "%{$term}%")
                      ->orWhere('email', $op, "%{$term}%")
                      ->orWhere('reference_number', $op, "%{$term}%");
                });
            })
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('category'), fn ($q, $c) => $q->where('talent_category', $c))
            ->orderByDesc('id')
            ->paginate($perPage);

        return TalentSubmissionResource::collection($submissions);
    }

    public function show(TalentSubmission $talentSubmission): TalentSubmissionResource
    {
        $talentSubmission->load('reviewer:id,name,email');

        return new TalentSubmissionResource($talentSubmission);
    }

    public function update(
        UpdateTalentSubmissionRequest $request,
        TalentSubmission $talentSubmission,
    ): TalentSubmissionResource {
        $data = $request->validated();
        $auditEntries = [];

        // Status change
        if (array_key_exists('status', $data) && $data['status'] !== $talentSubmission->status) {
            $auditEntries[] = [
                'action' => 'talent_submission.status_changed',
                'changes' => [
                    'from' => $talentSubmission->status,
                    'to' => $data['status'],
                ],
            ];

            // Auto-set reviewed_at when leaving 'pending'
            if (
                $talentSubmission->status === TalentSubmission::STATUS_PENDING
                && $data['status'] !== TalentSubmission::STATUS_PENDING
                && ! $talentSubmission->reviewed_at
            ) {
                $data['reviewed_at'] = now();
            }
        }

        // Manager notes change
        if (array_key_exists('manager_notes', $data)
            && $data['manager_notes'] !== $talentSubmission->manager_notes) {
            $auditEntries[] = [
                'action' => 'talent_submission.notes_updated',
                'changes' => [
                    'from' => $talentSubmission->manager_notes,
                    'to' => $data['manager_notes'],
                ],
            ];
        }

        // Reviewer change
        if (array_key_exists('reviewed_by', $data)
            && $data['reviewed_by'] !== $talentSubmission->reviewed_by) {
            $auditEntries[] = [
                'action' => 'talent_submission.reviewer_changed',
                'changes' => [
                    'from' => $talentSubmission->reviewed_by,
                    'to' => $data['reviewed_by'],
                ],
            ];

            if (! $talentSubmission->reviewed_at && $data['reviewed_by']) {
                $data['reviewed_at'] = now();
            }
        }

        $talentSubmission->update($data);

        foreach ($auditEntries as $entry) {
            AuditLog::log(
                $entry['action'],
                $talentSubmission,
                $entry['changes'],
            );
        }

        $talentSubmission->load('reviewer:id,name,email');

        return new TalentSubmissionResource($talentSubmission);
    }

    /**
     * Stream private media (audio / video / image) to authorized staff.
     * Public users cannot reach this — it lives under auth:sanctum.
     */
    public function media(
        Request $request,
        TalentSubmission $talentSubmission,
        string $type,
    ): StreamedResponse|JsonResponse {
        $allowed = ['audio', 'video', 'image'];

        if (! in_array($type, $allowed, true)) {
            return response()->json(['message' => 'Invalid media type.'], 404);
        }

        $path = match ($type) {
            'audio' => $talentSubmission->audio_path,
            'video' => $talentSubmission->video_path,
            'image' => $talentSubmission->image_path,
        };

        if (! $path) {
            return response()->json(['message' => 'Media not found.'], 404);
        }

        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Media file missing.'], 404);
        }

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=0, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}