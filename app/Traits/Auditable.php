<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            AuditLog::record(AuditLog::ACTION_CREATED, $model);
        });

        static::updated(function ($model) {
            AuditLog::record(AuditLog::ACTION_UPDATED, $model);
        });

        static::deleted(function ($model) {
            AuditLog::record(AuditLog::ACTION_DELETED, $model);
        });

        static::restored(function ($model) {
            AuditLog::record(AuditLog::ACTION_RESTORED, $model);
        });
    }

    public function getAuditLabel(): string
    {
        return '#' . $this->getKey();
    }
}