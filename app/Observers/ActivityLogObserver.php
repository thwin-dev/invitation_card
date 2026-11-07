<?php

namespace App\Observers;

use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    public function created(Model $model): void
    {
        ActivityLogger::recordModelChange($model, 'created', [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        $before = [];
        $after = [];

        foreach (array_keys($changes) as $field) {
            $before[$field] = $model->getRawOriginal($field);
            $after[$field] = $model->getAttributes()[$field] ?? null;
        }

        $action = array_key_exists('deleted_at', $changes) && $model->getAttribute('deleted_at') === null
            ? 'restored'
            : 'updated';

        ActivityLogger::recordModelChange($model, $action, $before, $after);
    }

    public function deleted(Model $model): void
    {
        $before = $model->getRawOriginal();
        $after = [];

        if (array_key_exists('deleted_at', $model->getAttributes())) {
            $before['deleted_at'] = $model->getRawOriginal('deleted_at');
            $after['deleted_at'] = $model->getAttribute('deleted_at');
        }

        ActivityLogger::recordModelChange($model, 'deleted', $before, $after);
    }
}
