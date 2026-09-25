<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    protected function generateUniqueSlug(): string
    {
        $source = $this->slugSource();
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'item-' . uniqid();
        }

        $slug = $base;
        $i = 2;

        $query = static::withTrashed();

        while ($query->clone()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    protected function slugSource(): string
    {
        return (string) ($this->name ?? $this->title ?? '');
    }
}