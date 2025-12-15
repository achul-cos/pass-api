<?php

namespace App\Rules\Api\V1\Tiket;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;

class ValidasiJumlahPenumpang implements ValidationRule, DataAwareRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jenisKendaraan = $this->data['jenisKendaraan'] ?? null;
        $jumlahPenumpang = count($value); // $value adalah array penumpangList

        if ($jenisKendaraan === 'motor' && $jumlahPenumpang > 2) {
            $fail('Untuk kendaraan motor, maksimal penumpang adalah 2 orang.');
        }

        if ($jenisKendaraan === 'mobil' && $jumlahPenumpang > 6) {
            $fail('Untuk kendaraan mobil, maksimal penumpang adalah 6 orang.');
        }
    }
}
