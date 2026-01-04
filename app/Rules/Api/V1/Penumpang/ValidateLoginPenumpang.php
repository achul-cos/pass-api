<?php

namespace App\Rules\Api\V1\Penumpang;

use Illuminate\Contracts\Translation\PotentiallyTranslatedString;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Penumpang;
use Closure;

class ValidateLoginPenumpang implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $login = trim($value);

        $query = Penumpang::query();

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $query->where('email', $login);
        } else {
            $query->where('nomor_telepon', $login);
        }

        if (! $query->exists()) {
            $fail('Email atau nomor telepon tidak terdaftar.');
        }
    }
}
