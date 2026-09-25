<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_hash',
        'token_id',
        'ip_address',
        'browser',
        'platform',
        'device',
        'location',
        'country_code',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateHash(string $ip, string $browser, string $platform): string
    {
        return hash('sha256', $ip . '|' . $browser . '|' . $platform);
    }
}