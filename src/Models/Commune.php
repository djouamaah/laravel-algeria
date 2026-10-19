<?php

namespace Djouamaah\Algeria\Models;

use Djouamaah\Algeria\Algeria;
use Djouamaah\Algeria\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $daira_id
 * @property int $wilaya_id
 * @property int $legacy_wilaya_id Wilaya in the 58-wilaya division (2019).
 * @property string $name
 * @property string $name_ar
 * @property string|null $postal_code Postal code of the commune's main post office.
 * @property float|null $latitude
 * @property float|null $longitude
 * @property-read string $localized_name
 */
class Commune extends Model
{
    use HasLocalizedName;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'daira_id' => 'integer',
        'wilaya_id' => 'integer',
        'legacy_wilaya_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function getTable(): string
    {
        return config('algeria.tables.communes', 'communes');
    }

    public function daira(): BelongsTo
    {
        return $this->belongsTo(Daira::class);
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    /**
     * Every post office postal code of the commune.
     */
    public function postalCodes(): HasMany
    {
        return $this->hasMany(PostalCode::class);
    }

    /**
     * Distance in kilometres from this commune to a point, or null when the commune has no coordinates.
     */
    public function distanceTo(float $latitude, float $longitude): ?float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return Algeria::distance($this->latitude, $this->longitude, $latitude, $longitude);
    }

    public function legacyWilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class, 'legacy_wilaya_id');
    }
}
