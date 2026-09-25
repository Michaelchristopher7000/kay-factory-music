<?php

use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DistributionController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicArtistController;
use App\Http\Controllers\Api\PublicReleaseController;
use App\Http\Controllers\Api\Public\ArtistMediaController;
use App\Http\Controllers\Api\Public\TalentSubmissionController as PublicTalentSubmissionController;
use App\Http\Controllers\Api\Public\ContactMessageController as PublicContactMessageController;
use App\Http\Controllers\Api\Public\EventController;
use App\Http\Controllers\Api\Public\VideoController;
use App\Http\Controllers\Api\ReleaseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\RevenueEntryController;
use App\Http\Controllers\Api\RoyaltyPaymentController;
use App\Http\Controllers\Api\RoyaltyStatementController;
use App\Http\Controllers\Api\RoyaltyStatementLineController;
use App\Http\Controllers\Api\TalentSubmissionController;
use App\Http\Controllers\Api\TrackController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SecurityActivityController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\Admin\ArtistVideoController;
use App\Http\Controllers\Api\Admin\ArtistGalleryController;
use App\Http\Controllers\Api\Admin\ArtistEventController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\StaffMessageController;
use Illuminate\Support\Facades\Route;


// ---------------------------------------------------------------
// PUBLIC AUTH
// ---------------------------------------------------------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/2fa', [AuthController::class, 'loginTwoFactor']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

// ---------------------------------------------------------------
// PUBLIC — content (no auth)
// ---------------------------------------------------------------
Route::prefix('public')->group(function () {
    Route::get('artists', [PublicArtistController::class, 'index']);
    Route::get('artists/{artist:slug}', [PublicArtistController::class, 'show']);

    // Public artist media (nested)
    Route::get('artists/{artist:slug}/videos', [ArtistMediaController::class, 'videos']);
    Route::get('artists/{artist:slug}/gallery', [ArtistMediaController::class, 'gallery']);
    Route::get('artists/{artist:slug}/events', [ArtistMediaController::class, 'events']);

    // Public talent submissions
    Route::post('talent-submissions', [PublicTalentSubmissionController::class, 'store'])
        ->middleware('throttle:5,60');

    // Public contact messages
    Route::post('contact-messages', [PublicContactMessageController::class, 'store'])
        ->middleware('throttle:5,60');

    // Public events — next upcoming event across the label
    Route::get('events/next', [EventController::class, 'next']);

    // Public videos — the single video featured on the home page
    Route::get('videos/featured', [VideoController::class, 'featured']);

    Route::get('releases', [PublicReleaseController::class, 'index']);
    Route::get('releases/{release:slug}', [PublicReleaseController::class, 'show']);
});

// ---------------------------------------------------------------
// AUTHENTICATED
// ---------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::patch('/me', [ProfileController::class, 'update']);
    Route::post('/me/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('/me/avatar', [ProfileController::class, 'deleteAvatar']);
    Route::post('/me/password', [PasswordController::class, 'changePassword']);

    Route::middleware('role:super-admin')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);

        Route::post('me/2fa/setup', [TwoFactorController::class, 'setup']);
        Route::post('me/2fa/verify', [TwoFactorController::class, 'verify']);
        Route::post('me/2fa/disable', [TwoFactorController::class, 'disable']);
        Route::post('me/2fa/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
    });

    Route::apiResource('artists', ArtistController::class);
    Route::apiResource('contracts', ContractController::class);
    Route::apiResource('tracks', TrackController::class);
    Route::apiResource('releases', ReleaseController::class);

    // Artist media (admin CRUD)
    Route::apiResource('artist-videos', ArtistVideoController::class)
        ->parameters(['artist-videos' => 'video']);
    Route::apiResource('artist-gallery', ArtistGalleryController::class)
        ->parameters(['artist-gallery' => 'image']);
    Route::apiResource('artist-events', ArtistEventController::class)
        ->parameters(['artist-events' => 'event']);

    // Talent Submissions (admin)
    Route::middleware('role:super-admin,label-manager')->group(function () {
        Route::get('talent-submissions', [TalentSubmissionController::class, 'index']);
        Route::get('talent-submissions/{talentSubmission}', [TalentSubmissionController::class, 'show']);
        Route::patch('talent-submissions/{talentSubmission}', [TalentSubmissionController::class, 'update']);
        Route::get(
            'talent-submissions/{talentSubmission}/media/{type}',
            [TalentSubmissionController::class, 'media']
        )->where('type', 'audio|video|image');
    });

    // Contact Messages (admin)
    Route::middleware('role:super-admin,label-manager')->group(function () {
        Route::get('contact-messages', [ContactMessageController::class, 'index']);
        Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show']);
        Route::patch('contact-messages/{contactMessage}', [ContactMessageController::class, 'update']);
    });

    // Staff Messaging
    Route::prefix('staff-messages')->group(function () {
        Route::get('conversations', [StaffMessageController::class, 'conversations']);
        Route::get('conversations/{conversation}', [StaffMessageController::class, 'show']);
        Route::post('conversations/{conversation}/messages', [StaffMessageController::class, 'send']);
        Route::post('conversations', [StaffMessageController::class, 'start']);
        Route::get('recipients', [StaffMessageController::class, 'recipients']);
        Route::get('unread-count', [StaffMessageController::class, 'unreadCount']);
    });

    Route::apiResource('distributions', DistributionController::class);

    Route::apiResource('revenue-entries', RevenueEntryController::class)
        ->parameters(['revenue-entries' => 'revenueEntry']);
    Route::apiResource('expenses', ExpenseController::class);

    Route::post(
        'royalty-statements/{royaltyStatement}/regenerate',
        [RoyaltyStatementController::class, 'regenerate']
    );
    Route::apiResource('royalty-statements', RoyaltyStatementController::class)
        ->parameters(['royalty-statements' => 'royaltyStatement']);
    Route::apiResource('royalty-statement-lines', RoyaltyStatementLineController::class)
        ->only(['index', 'update', 'destroy'])
        ->parameters(['royalty-statement-lines' => 'royaltyStatementLine']);

    Route::apiResource('royalty-payments', RoyaltyPaymentController::class)
        ->parameters(['royalty-payments' => 'royaltyPayment']);

    Route::prefix('reports')->group(function () {
        Route::get('releases/performance', [ReportController::class, 'releasePerformance']);
        Route::get('distributions/status', [ReportController::class, 'distributionStatus']);

        Route::middleware('role:super-admin,label-manager,finance-staff')->group(function () {
            Route::get('artists/summary', [ReportController::class, 'artistSummary']);
            Route::get('revenue/by-source', [ReportController::class, 'revenueBySource']);
            Route::get('expenses/by-category', [ReportController::class, 'expenseByCategory']);
            Route::get('royalties/balances', [ReportController::class, 'royaltyBalances']);
        });
    });

    Route::middleware('role:super-admin,label-manager')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index']);
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    });

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    Route::get('devices', [DeviceController::class, 'index']);
    Route::post('devices/revoke-others', [DeviceController::class, 'revokeOthers']);
    Route::post('devices/revoke-all', [DeviceController::class, 'revokeAll']);
    Route::delete('devices/{device}', [DeviceController::class, 'destroy']);

    Route::get('me/security/activity', [SecurityActivityController::class, 'index']);
});