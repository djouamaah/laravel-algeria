<?php

namespace Djouamaah\Algeria\Rules;

use Closure;
use Djouamaah\Algeria\Algeria;
use Illuminate\Contracts\Validation\ValidationRule;

abstract class AlgeriaRule implements ValidationRule
{
    abstract protected function passes(Algeria $algeria, int|string $value): bool;

    abstract protected function messageKey(): string;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (is_int($value) || is_string($value)) || ! $this->passes(app(Algeria::class), $value)) {
            $fail('algeria::validation.'.$this->messageKey())->translate();
        }
    }
}
