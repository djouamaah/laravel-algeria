<?php

namespace Djouamaah\Algeria\Models;

use Djouamaah\Algeria\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $name_ar
 * @property int|null $parent_id
 * @property-read string $localized_name
 */
class Wilaya extends Model
{
    use HasLocalizedName;

    protected $guarded = [];

    protected $casts = ['id' => 'integer', 'parent_id' => 'integer'];

    public function getTable(): string
    {
        return config('algeria.tables.wilayas', 'wilayas');
    }

    public function dairas(): HasMany
    {
        return $this->hasMany(Daira::class);
    }

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }

    /**
     * The wilaya this one was split from (2026 division only).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function scopeCode(Builder $query, int|string $code): Builder
    {
        return $query->where('code', str_pad((string) (int) $code, 2, '0', STR_PAD_LEFT));
    }
}
