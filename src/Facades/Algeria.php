<?php

namespace Djouamaah\Algeria\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static int division()
 * @method static \Djouamaah\Algeria\Algeria withDivision(int $division)
 * @method static Collection wilayas()
 * @method static array|null wilaya(int|string $wilaya)
 * @method static Collection dairas(int|string|null $wilaya = null)
 * @method static array|null daira(int|string $daira)
 * @method static Collection communes(int|string|null $wilaya = null)
 * @method static Collection communesOfDaira(int|string $daira)
 * @method static array|null commune(int|string $commune)
 * @method static Collection postalCodes(int|string|null $commune = null)
 * @method static array|null postalCode(int|string $code)
 * @method static array|null communeByPostalCode(int|string $code)
 * @method static Collection nearest(float $latitude, float $longitude, int $limit = 1, int|string|null $wilaya = null)
 * @method static float distance(float $lat1, float $lng1, float $lat2, float $lng2)
 * @method static Collection search(string $term, int|string|null $wilaya = null)
 *
 * @see \Djouamaah\Algeria\Algeria
 */
class Algeria extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Djouamaah\Algeria\Algeria::class;
    }
}
