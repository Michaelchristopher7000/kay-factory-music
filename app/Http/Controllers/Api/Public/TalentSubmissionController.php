<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTalentSubmissionRequest;
use App\Models\AuditLog;
use App\Models\TalentSubmission;
use App\Models\User;
use App\Notifications\TalentSubmissionReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TalentSubmissionController extends Controller
{
    public function store(StoreTalentSubmissionRequest $request): JsonResponse
    {
        $data = $request->validated();

        unset($data['audio'], $data['video'], $data['image'], $data['consent']);

        $folder = 'talent-submissions/' . Str::uuid()->toString();
        $uploaded = [];

        try {
            if ($request->hasFile('audio')) {
                $path = $request->file('audio')->store("{$folder}/audio", 'local');
                $data['audio_path'] = $path;
                $uploaded[] = $path;
            }

            if ($request->hasFile('video')) {
                $path = $request->file('video')->store("{$folder}/video", 'local');
                $data['video_path'] = $path;
                $uploaded[] = $path;
            }

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store("{$folder}/image", 'local');
                $data['image_path'] = $path;
                $uploaded[] = $path;
            }

            $submission = DB::transaction(function () use ($data) {
                $data['status'] = TalentSubmission::STATUS_PENDING;

                $submission = TalentSubmission::create($data);

                AuditLog::log(
                    'created',
                    $submission,
                    ['attributes' => $submission->getAttributes()],
                );

                return $submission;
            });

            // Notify super-admins + label managers. Wrapped in try/catch so a
            // mail/SMTP failure never fails the public submission.
            $this->notifyStaff($submission);

            return response()->json([
                'message' => 'Your talent submission has been received successfully.',
                'reference_number' => $submission->reference_number,
            ], 201);
        } catch (Throwable $e) {
            foreach ($uploaded as $path) {
                try {
                    Storage::disk('local')->delete($path);
                } catch (Throwable) {
                    // best-effort cleanup
                }
            }

            report($e);

            return response()->json([
                'message' => 'We could not process your submission. Please try again.',
            ], 500);
        }
    }

    /**
     * Send the in-database + email notification to super-admins and
     * label-managers. Any failure here is logged but never bubbles up —
     * the submission itself must succeed regardless of mail status.
     */
    protected function notifyStaff(TalentSubmission $submission): void
    {
        try {
            $recipients = User::query()
                ->whereHas('role', function ($q) {
                    $q->whereIn('slug', ['super-admin', 'label-manager']);
                })
                ->get();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send(
                $recipients,
                new TalentSubmissionReceivedNotification($submission),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}