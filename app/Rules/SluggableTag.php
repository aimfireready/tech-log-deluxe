<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Tags are identified by slug, so a name whose slug is empty (only symbols, or a script
 * Str::slug can't transliterate) would be silently dropped. Reject it instead.
 */
class SluggableTag implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && trim($value) !== '' && Str::slug($value) === '') {
            $fail('Tags need at least one Latin letter or number.');
        }
    }
}
