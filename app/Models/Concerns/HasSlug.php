<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

/**
 * Fills the slug column from the source attribute when it is blank,
 * appending -2, -3, ... until the slug is unique.
 *
 * @mixin Model
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function (Model $model): void {
            if (filled($model->getAttribute(static::slugColumn()))) {
                return;
            }

            $source = $model->getAttribute(static::slugSource());

            $model->setAttribute(
                static::slugColumn(),
                static::generateUniqueSlug(is_string($source) ? $source : ''),
            );
        });
    }

    public static function generateUniqueSlug(string $value, ?int $ignoreKey = null): string
    {
        $base = Str::slug($value);
        $slug = $base;

        for ($suffix = 2; static::slugExists($slug, $ignoreKey); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    protected static function slugColumn(): string
    {
        return 'slug';
    }

    protected static function slugSource(): string
    {
        return 'name';
    }

    private static function slugExists(string $slug, ?int $ignoreKey): bool
    {
        return static::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->where(static::slugColumn(), $slug)
            ->when($ignoreKey !== null, fn ($query) => $query->whereKeyNot($ignoreKey))
            ->exists();
    }
}
