<?php

namespace App\Rules\Api\V1\Tiket;

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
        $jadwal = Jadwal::find($value);

        if (!$jadwal) {
            $fail('Jadwal Tidak Ditemukan');
            return;
        }

        $waktuBerangkat = Carbon::parse($jadwal->waktu_berangkat);
        $now = Carbon::now();

        $jamMenujuBerangkat = $now->diffInHours($waktuBerangkat, false);

        if ($jamMenujuBerangkat <= 3) {
            $fail('Jadwal yang anda pilih tidak dapat dibeli karena pembelian tiket hanya bisa dilakukan maksimal 3 jam sebelum waktu berangkat.');
        }
    }
}
