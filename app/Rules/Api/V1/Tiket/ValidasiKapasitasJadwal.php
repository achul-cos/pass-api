<?php

namespace App\Rules\Api\V1\Tiket;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use App\Models\Jadwal;
use App\Models\Tiket;

class ValidasiKapasitasJadwal implements ValidationRule, DataAwareRule
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
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jadwalId = $value;
        $jenisKendaraanInput = $this->data['jenisKendaraan'] ?? null;

        // Jika jenis kendaraan tidak valid/kosong, skip (biar rule lain yang handle)
        if (!in_array($jenisKendaraanInput, ['motor', 'mobil'])) {
            return;
        }

        // 1. Ambil Data Jadwal untuk tau kapasitas maksimalnya
        $jadwal = Jadwal::select('id', 'kapasitas')->find($jadwalId);
        if (!$jadwal) {
            return; // Skip jika jadwal tidak ditemukan (dihandle exists)
        }

        // 2. Hitung Kapasitas yang Sedang Terpakai (Existing Tickets)
        // Menggunakan query aggregate agar lebih efisien daripada loop PHP
        // Motor = 2, Mobil = 6
        $kapasitasTerpakai = Tiket::where('jadwal_id', $jadwalId)
            ->selectRaw("SUM(CASE 
                WHEN jenis_kendaraan = 'motor' THEN 2 
                WHEN jenis_kendaraan = 'mobil' THEN 6 
                ELSE 0 
            END) as total_load")
            ->value('total_load') ?? 0;

        // 3. Hitung Kapasitas Request Baru
        $kapasitasRequest = ($jenisKendaraanInput === 'mobil') ? 6 : 2;

        // 4. Validasi Total
        $totalKapasitasDibutuhkan = $kapasitasTerpakai + $kapasitasRequest;

        if ($totalKapasitasDibutuhkan > $jadwal->kapasitas) {
            $sisaKapasitas = max(0, $jadwal->kapasitas - $kapasitasTerpakai);
            $fail("Kapasitas jadwal tidak mencukupi. Kapasitas tersisa: {$sisaKapasitas} (Butuh: {$kapasitasRequest})");
        }
    }
}
