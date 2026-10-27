<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait GeneratesSlugs
{
    /** Unique slug across the table, including soft-deleted rows (the unique index covers them). */
    protected function uniqueSlug(Model $model, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 2;

        while ($model->newQuery()->withTrashed()->where('slug', $slug)->whereKeyNot($model->getKey())->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
