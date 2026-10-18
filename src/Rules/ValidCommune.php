<?php

namespace Djouamaah\Algeria\Rules;

use Djouamaah\Algeria\Algeria;

/**
 * 'commune_id' => ['required', new ValidCommune(wilaya: $request->wilaya_id)]
 * 'commune_id' => ['required', new ValidCommune(daira: $request->daira_id)]
 */
class ValidCommune extends AlgeriaRule
{
    public function __construct(
        protected int|string|null $wilaya = null,
        protected int|string|null $daira = null,
    ) {
    }

    protected function passes(Algeria $algeria, int|string $value): bool
    {
        $commune = $algeria->commune($value);

        if ($commune === null) {
            return false;
        }

        if ($this->wilaya !== null && $commune['wilaya_id'] !== ($algeria->wilaya($this->wilaya)['id'] ?? null)) {
            return false;
        }

        return $this->daira === null || $commune['daira_id'] === ($algeria->daira($this->daira)['id'] ?? null);
    }

    protected function messageKey(): string
    {
        return match (true) {
            $this->daira !== null => 'commune_in_daira',
            $this->wilaya !== null => 'commune_in_wilaya',
            default => 'commune',
        };
    }
}
