<?php

namespace Djouamaah\Algeria\Rules;

use Djouamaah\Algeria\Algeria;

/**
 * 'wilaya_id' => ['required', new ValidWilaya]
 */
class ValidWilaya extends AlgeriaRule
{
    protected function passes(Algeria $algeria, int|string $value): bool
    {
        return $algeria->wilaya($value) !== null;
    }

    protected function messageKey(): string
    {
        return 'wilaya';
    }
}
