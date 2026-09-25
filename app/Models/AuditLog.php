<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';

    public const ACTIONS = [
        self::ACTION_CREATED,
        self::ACTION_UPDATED,
        self::ACTION_DELETED,
        self::ACTION_RESTORED,
    ];

    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        'api_token',
        'personal_access_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'reset_token',
    ];

    protected $fillable = [
        'user_id',
        'user_email',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'model_label',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, Model $model): void
    {
        try {
            $changes = static::buildChanges($action, $model);

            if ($action === self::ACTION_UPDATED && empty($changes)) {
                return;
            }

            static::write($action, $model, $changes);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Record an explicit audit entry for a custom action.
     * Caller supplies the changes payload.
     *
     * @param  array{before?:array,after?:array,attributes?:array}  $changes
     * @param  User|null  $subject
     *   Optional. When provided, this user is written into the user_id /
     *   user_email / user_name columns instead of the currently authenticated
     *   user. Use only for security events where the affected account is
     *   known but no one is authenticated (login_failed, password_reset_requested).
     *   When null (default), behavior is unchanged.
     */
    public static function log(
        string $action,
        Model $model,
        array $changes = [],
        ?User $subject = null,
    ): void {
        try {
            static::write($action, $model, static::filterSensitive($changes), $subject);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected static function write(
        string $action,
        Model $model,
        array $changes,
        ?User $subject = null,
    ): void {
        // Existing behavior: use the authenticated user.
        // When $subject is provided explicitly, use it instead.
        $subject = $subject ?? Auth::user();

        $ip = null;
        $userAgent = null;

        if (! app()->runningInConsole() && app()->bound('request')) {
            $ip = request()->ip();
            $userAgent = request()->userAgent();
        }

        $label = method_exists($model, 'getAuditLabel')
            ? (string) $model->getAuditLabel()
            : '#' . $model->getKey();

        if ($label === '') {
            $label = '#' . $model->getKey();
        }

        static::create([
            'user_id' => $subject?->id,
            'user_email' => $subject?->email ?? 'system',
            'user_name' => $subject?->name ?? 'system',
            'action' => $action,
            'model_type' => class_basename($model),
            'model_id' => $model->getKey(),
            'model_label' => $label,
            'changes' => $changes ?: null,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    protected static function buildChanges(string $action, Model $model): array
    {
        $exclude = array_merge(
            ['created_at', 'updated_at', 'deleted_at'],
            $model->getHidden(),
            property_exists($model, 'auditHidden') && is_array($model->auditHidden)
                ? $model->auditHidden
                : [],
            self::SENSITIVE_KEYS
        );

        if ($action === self::ACTION_CREATED) {
            return ['attributes' => static::filterAttributes($model->getAttributes(), $exclude)];
        }

        if ($action === self::ACTION_UPDATED) {
            $changed = $model->getChanges();

            if (count($changed) <= 1 && array_key_exists('updated_at', $changed)) {
                return [];
            }

            if (count($changed) === 1 && array_key_exists('deleted_at', $changed)) {
                return [];
            }

            $before = [];
            $after = [];

            foreach ($changed as $key => $newValue) {
                if (in_array($key, $exclude, true)) {
                    continue;
                }
                $before[$key] = $model->getOriginal($key);
                $after[$key] = $newValue;
            }

            if (empty($before) && empty($after)) {
                return [];
            }

            return ['before' => $before, 'after' => $after];
        }

        return ['attributes' => static::filterAttributes($model->getAttributes(), $exclude)];
    }

    protected static function filterAttributes(array $attributes, array $exclude): array
    {
        return array_diff_key($attributes, array_flip($exclude));
    }

    protected static function filterSensitive(array $changes): array
    {
        $filtered = [];

        foreach ($changes as $key => $value) {
            if (in_array($key, self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $filtered[$key] = static::filterSensitive($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    public static function likeOperator(): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql'
            ? 'ilike'
            : 'like';
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! empty($term)) {
            $op = static::likeOperator();

            $query->where(function ($q) use ($term, $op) {
                $q->where('model_label', $op, "%{$term}%")
                  ->orWhere('user_email', $op, "%{$term}%")
                  ->orWhere('user_name', $op, "%{$term}%");
            });
        }

        return $query;
    }

    public function scopeForUser($query, $userId)
    {
        if (! empty($userId)) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    public function scopeAction($query, ?string $action)
    {
        if (! empty($action)) {
            $query->where('action', $action);
        }

        return $query;
    }

    public function scopeModelType($query, ?string $type)
    {
        if (empty($type)) {
            return $query;
        }

        $type = class_basename($type);

        $query->whereRaw('LOWER(model_type) = ?', [strtolower($type)]);

        return $query;
    }

    public function scopeForModel($query, $modelId)
    {
        if (! empty($modelId)) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    public function scopeFromDate($query, $from)
    {
        if (! empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }

        return $query;
    }

    public function scopeToDate($query, $to)
    {
        if (! empty($to)) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }
}