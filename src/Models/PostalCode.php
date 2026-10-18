<?php

namespace Djouamaah\Algeria\Models;

use Djouamaah\Algeria\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A post office and its postal code.
 *
 * @property string $code
 * @property int $commune_id
 * @property string $name
 * @property string $name_ar
 * @property-read string $localized_name
 */
class PostalCode extends Model
{
    use HasLocalizedName;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['commune_id' => 'integer'];

    public function getTable(): string
    {
        return config('algeria.tables.postal_codes', 'postal_codes');
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }
}
