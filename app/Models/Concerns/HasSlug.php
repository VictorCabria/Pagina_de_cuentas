<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Genera un slug único a partir de "name" cuando se deja vacío.
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            if (filled($model->slug)) {
                return;
            }

            $base = Str::slug($model->name) ?: 'item';
            $slug = $base;
            $i = 2;

            while (static::where('slug', $slug)->when($model->id, fn ($q) => $q->whereKeyNot($model->id))->exists()) {
                $slug = $base.'-'.$i++;
            }

            $model->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
