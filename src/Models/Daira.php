<?php

namespace Djouamaah\Algeria\Models;

use Djouamaah\Algeria\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $wilaya_id
 * @property string $name
 * @property string $name_ar
 * @property-read string $localized_name
 */
class Daira extends Model
{
    use HasLocalizedName;

    protected $guarded = [];

    protected $casts = ['id' => 'integer', 'wilaya_id' => 'integer'];

    public function getTable(): string
    {
        return config('algeria.tables.dairas', 'dairas');
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }
}
