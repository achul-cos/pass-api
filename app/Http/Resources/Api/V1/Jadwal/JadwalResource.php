<?php

namespace App\Http\Resources\Api\V1\Jadwal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'namaJadwal' => $this->nama_jadwal,
            'waktuBerangkat' => $this->waktu_berangkat,
            'waktuTiba' => $this->waktu_tiba,
            'lokasiBerangkat' => $this->lokasi_berangkat,
            'lokasiTiba' => $this->lokasi_tiba,
            'biayaPerjalanan' => $this->biaya_perjalanan,
            'biayaPenumpang' => $this->biaya_penumpang,
            'biayaMotor' => $this->biaya_motor,
            'biayaMobil' => $this->biaya_mobil,
            'diskon' => $this->diskon,
            'pajak' => $this->pajak,
            'kapasitas' => $this->kapasitas,
            'namaKapal' => $this->nama_kapal,
        ];
    }
}
