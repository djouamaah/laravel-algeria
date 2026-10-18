<?php

namespace Djouamaah\Algeria\Rules;

use Djouamaah\Algeria\Algeria;

/**
 * 'daira_id' => ['required', new ValidDaira($request->wilaya_id)]
 */
class ValidDaira extends AlgeriaRule
{
    public function __construct(protected int|string|null $wilaya = null)
    {
    }

    protected function passes(Algeria $algeria, int|string $value): bool
    {
        $daira = $algeria->daira($value);

        return $daira !== null
            && ($this->wilaya === null || $daira['wilaya_id'] === ($algeria->wilaya($this->wilaya)['id'] ?? null));
    }

    protected function messageKey(): string
    {
        return $this->wilaya === null ? 'daira' : 'daira_in_wilaya';
    }
}
