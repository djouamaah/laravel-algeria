<?php

namespace Djouamaah\Algeria\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLocalizedName
{
    public function initializeHasLocalizedName(): void
    {
        $this->timestamps = false;
        $this->incrementing = false;
        $this->append('localized_name');
    }

    /**
     * Arabic name when the app locale is "ar", Latin name otherwise.
     */
    public function getLocalizedNameAttribute(): ?string
    {
        return str_starts_with(app()->getLocale(), 'ar') ? $this->name_ar : $this->name;
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('name_ar', 'like', "%{$term}%"));
    }

    public function scopeOrderByName(Builder $query, ?string $locale = null): Builder
    {
        $locale ??= app()->getLocale();

        return $query->orderBy(str_starts_with($locale, 'ar') ? 'name_ar' : 'name');
    }
}
