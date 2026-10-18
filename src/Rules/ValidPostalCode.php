<?php

namespace Djouamaah\Algeria\Rules;

use Djouamaah\Algeria\Algeria;

/**
 * 'postal_code' => ['required', new ValidPostalCode]
 * 'postal_code' => ['required', new ValidPostalCode(commune: $request->commune_id)]
 */
class ValidPostalCode extends AlgeriaRule
{
    public function __construct(protected int|string|null $commune = null)
    {
    }

    protected function passes(Algeria $algeria, int|string $value): bool
    {
        $postalCode = $algeria->postalCode($value);

        return $postalCode !== null
            && ($this->commune === null || $postalCode['commune_id'] === ($algeria->commune($this->commune)['id'] ?? null));
    }

    protected function messageKey(): string
    {
        return $this->commune === null ? 'postal_code' : 'postal_code_in_commune';
    }
}
