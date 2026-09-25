<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login Throttling
    |--------------------------------------------------------------------------
    |
    | Failed login attempts are tracked per-email AND per-IP. Once either
    | threshold is exceeded, further attempts are rejected with HTTP 429
    | for the duration specified in `decay_seconds`.
    |
    */

    'login' => [
        'max_attempts'    => (int) env('KFM_LOGIN_MAX_ATTEMPTS', 5),
        'ip_max_attempts' => (int) env('KFM_LOGIN_IP_MAX_ATTEMPTS', 20),
        'decay_seconds'   => (int) env('KFM_LOGIN_DECAY_SECONDS', 900), // 15 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Strength
    |--------------------------------------------------------------------------
    |
    | Central password policy used by Password::defaults() everywhere.
    |
    */

       'password' => [
        'min_length'         => (int) env('KFM_PASSWORD_MIN_LENGTH', 12),
        'require_mixed_case' => (bool) env('KFM_PASSWORD_MIXED_CASE', false),
        'require_numbers'    => (bool) env('KFM_PASSWORD_NUMBERS', false),
        'require_symbols'    => (bool) env('KFM_PASSWORD_SYMBOLS', false),
        'check_compromised'  => (bool) env('KFM_PASSWORD_CHECK_COMPROMISED', false),
    ],

        /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */

    'two_factor' => [
        'challenge_ttl_seconds' => (int) env('KFM_2FA_CHALLENGE_TTL_SECONDS', 300),      // 5 minutes
        'max_verify_attempts'   => (int) env('KFM_2FA_MAX_VERIFY_ATTEMPTS', 5),           // per challenge
        'ip_max_attempts'       => (int) env('KFM_2FA_IP_MAX_ATTEMPTS', 10),              // per IP
        'decay_seconds'         => (int) env('KFM_2FA_DECAY_SECONDS', 900),               // 15 minutes
        'recovery_code_count'   => (int) env('KFM_2FA_RECOVERY_CODE_COUNT', 8),
    ],

];