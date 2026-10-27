<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create/update/delete events to audit_logs. Hidden attributes
 * (passwords, tokens) and timestamps are never written to the log.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => AuditLog::record('created', $model, [], $model->auditValues($model->getAttributes())));

        static::updated(function (Model $model) {
            $changed = $model->auditValues($model->getChanges());
            if ($changed === []) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changed);
            AuditLog::record('updated', $model, $model->auditValues($original), $changed);
        });

        static::deleted(fn (Model $model) => AuditLog::record('deleted', $model, $model->auditValues($model->getAttributes()), []));
    }

    protected function auditValues(array $values): array
    {
        $excluded = array_merge($this->getHidden(), ['created_at', 'updated_at', 'remember_token']);

        return array_diff_key($values, array_flip($excluded));
    }
}
