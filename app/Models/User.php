<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Return the correct URL for the user's avatar.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->avatar)) {
                    return null;
                }

                // Keep existing full URLs working.
                if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                    return $this->avatar;
                }

                // Build the public Supabase URL for staff avatars.
                if (str_starts_with($this->avatar, 'avatars/')) {
                    $baseUrl = rtrim(
                        config('filesystems.disks.supabase.public_url', ''),
                        '/'
                    );

                    $bucket = config(
                        'filesystems.disks.supabase.bucket',
                        'kfm-media'
                    );

                    if ($baseUrl !== '') {
                        return $baseUrl
                            . '/storage/v1/object/public/'
                            . $bucket
                            . '/'
                            . $this->avatar;
                    }

                    return null;
                }

                // Preserve support for older locally stored avatars.
                return Storage::disk('public')->url($this->avatar);
            }
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null
            && $this->two_factor_confirmed_at !== null;
    }

    public function getAuditLabel(): string
    {
        return $this->name . ' (' . $this->email . ')';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}