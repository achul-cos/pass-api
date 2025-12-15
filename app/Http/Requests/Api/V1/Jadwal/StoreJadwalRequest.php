<?php

namespace App\Http\Requests\Api\V1\Jadwal;

use App\Rules\Api\V1\Jadwal\ValidasiWaktuBerangkat;
use Illuminate\Foundation\Http\FormRequest;

class StoreJadwalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Aturan Validasi Data Saat Di Input
        return [
            'namaJadwal' => ['required', 'string'],
            'waktuBerangkat' => ['required', 'date', new ValidasiWaktuBerangkat],
            'waktuTiba' => ['required', 'date'],
            'lokasiBerangkat' => ['required', 'string'],
            'lokasiTiba' => ['required', 'string'],
            'biayaPerjalanan' => ['required', 'numeric'],
            'biayaPenumpang' => ['required', 'numeric'],
            'biayaMotor' => ['required', 'numeric'],
            'biayaMobil' => ['required', 'numeric'],
            'diskon' => ['numeric'],
            'pajak' => ['numeric'],
            'kapasitas' => ['required', 'numeric'],
            'namaKapal' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        // Pesan Validasi Jika Validasi Gagal
        return [
            'namaJadwal.required' => 'Nama Jadwal Wajib Diisi',
            'waktuBerangkat.required' => 'Waktu Berangkat Wajib Diisi',
            'waktuTiba.required' => 'Waktu Tiba Wajib Diisi',
            'lokasiBerangkat.required' => 'Lokasi Berangkat Wajib Diisi',
            'lokasiTiba.required' => 'Lokasi Tiba Wajib Diisi',
            'biayaPerjalanan.required' => 'Biaya Perjalanan Wajib Diisi',
            'biayaPenumpang.required' => 'Biaya Penumpang Wajib Diisi',
            'biayaMotor.required' => 'Biaya Motor Wajib Diisi',
            'biayaMobil.required' => 'Biaya Mobil Wajib Diisi',
            'kapasitas.required' => 'Kapasitas Wajib Diisi',
            'namaKapal.required' => 'Nama Kapal Wajib Diisi',
            'biayaPerjalanan.numeric' => 'Biaya Perjalanan harus hanya berupa angka',
            'biayaPenumpang.numeric' => 'Biaya Penumpang harus hanya berupa angka',
            'biayaMotor.numeric' => 'Biaya Motor harus hanya berupa angka',
            'biayaMobil.numeric' => 'Biaya Mobil harus hanya berupa angka',
            'diskon.numeric' => 'Diskon harus hanya berupa angka',
            'pajak.numeric' => 'Pajak harus hanya berupa angka',
            'kapasitas.numeric' => 'Kapasitas hanya berupa angka',
            'waktuBerangkat.date' => 'Waktu Berangkat harus berupa tanggal',
            'waktuTiba.date' => 'Waktu Tiba harus berupa tanggal',
        ];
    }
}
