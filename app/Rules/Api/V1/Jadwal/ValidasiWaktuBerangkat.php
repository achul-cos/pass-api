<?php

namespace App\Rules\Api\V1\Jadwal;

use Illuminate\Contracts\Translation\PotentiallyTranslatedString;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Jadwal;
use Carbon\Carbon;
use Closure;

class ValidasiWaktuBerangkat implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $waktuBerangkat = $value;

        $selisihWaktuBerangkat = Carbon::now()->diffInHours($waktuBerangkat, false);

        if ($selisihWaktuBerangkat <= 3) {
            $fail('Jadwal hanya bisa dibuat jika waktu berangkat minimal 3 jam dari waktu sekarang.');
        }
    }
}
