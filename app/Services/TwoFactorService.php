<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Google2FA;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        /** @var Google2FA $service */
        $service = app()->make('pragmarx.google2fa');

        // The Laravel wrapper defaults to session-based state.
        // We're an API — force stateless mode so no session is touched.
        $service->setStateless(true);

        $this->google2fa = $service;
    }

    /**
     * Generate a new Base32 TOTP secret.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Verify a TOTP code against the given secret.
     * Uses the configured drift window (default ±1 step).
     */
    public function verifyCode(string $secret, string $code): bool
    {
        if ($secret === '' || $code === '') {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Render a QR code as an inline data URI (SVG).
     *
     * The underlying package returns raw SVG markup — we wrap it as a
     * proper data URI so browsers can render it in an <img src="...">.
     */
    public function qrCodeDataUri(string $holder, string $secret): string
    {
        $svg = $this->google2fa->getQRCodeInline(
            config('app.name', 'Kay Factory Music'),
            $holder,
            $secret,
        );

        $trimmed = ltrim((string) $svg);

        // Already a data URI — return as-is.
        if (str_starts_with($trimmed, 'data:')) {
            return $svg;
        }

        // Raw SVG markup — wrap as base64 data URI.
        if (str_starts_with($trimmed, '<?xml') || str_starts_with($trimmed, '<svg')) {
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        // Unknown format — best-effort wrap so the frontend doesn't break.
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Build the otpauth:// URL for manual entry.
     */
    public function qrCodeUrl(string $holder, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'Kay Factory Music'),
            $holder,
            $secret,
        );
    }

    /**
     * Generate N recovery codes in the format XXXXX-XXXXX.
     * Returns both plaintext (shown ONCE to the user) and bcrypt hashes
     * (the only values that persist in the database).
     *
     * @return array{plaintext: array<int,string>, hashed: array<int,string>}
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $plaintext = [];
        $hashed = [];

        for ($i = 0; $i < $count; $i++) {
            $code = $this->generateSingleRecoveryCode();
            $plaintext[] = $code;
            $hashed[] = Hash::make($code);
        }

        return [
            'plaintext' => $plaintext,
            'hashed'    => $hashed,
        ];
    }

    /**
     * Generate one recovery code: 5 chars, hyphen, 5 chars.
     * Alphabet excludes 0/O/1/I/L to avoid transcription confusion.
     */
    protected function generateSingleRecoveryCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $len = strlen($alphabet);

        $segment = function () use ($alphabet, $len): string {
            $out = '';
            for ($i = 0; $i < 5; $i++) {
                $out .= $alphabet[random_int(0, $len - 1)];
            }
            return $out;
        };

        return $segment() . '-' . $segment();
    }

    /**
     * Find a matching recovery code.
     * Returns the index of the matching hash, or null if none match.
     *
     * Always iterates the full list (no early return) to reduce the
     * ability of an attacker to infer which slot matched via timing.
     *
     * @param  array<int,string>  $hashes
     */
    public function findMatchingRecoveryCode(array $hashes, string $input): ?int
    {
        if ($input === '' || empty($hashes)) {
            return null;
        }

        $match = null;

        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && Hash::check($input, $hash)) {
                $match = $index;
            }
        }

        return $match;
    }
}