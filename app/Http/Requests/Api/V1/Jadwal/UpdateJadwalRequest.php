<?php

namespace App\Http\Requests\Api\V1\Jadwal;

use App\Rules\Api\V1\Jadwal\ValidasiWaktuBerangkat;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJadwalRequest extends FormRequest
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
        return [
            'namaJadwal' => ['sometimes', 'string', 'min:3'],
            'waktuBerangkat' => ['sometimes', 'date', new ValidasiWaktuBerangkat],
            'waktuTiba' => ['sometimes', 'date'],
            'lokasiBerangkat' => ['sometimes', 'string'],
            'lokasiTiba' => ['sometimes', 'string'],
            'biayaPerjalanan' => ['sometimes', 'numeric'],
            'biayaPenumpang' => ['sometimes', 'numeric'],
            'biayaMotor' => ['sometimes', 'numeric'],
            'biayaMobil' => ['sometimes', 'numeric'],
            'diskon' => ['sometimes', 'numeric'],
            'pajak' => ['sometimes', 'numeric'],
            'kapasitas' => ['sometimes', 'numeric'],
            'namaKapal' => ['sometimes', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'biayaPerjalanan.numeric' => 'Biaya Perjalanan harus hanya berupa angka',
            'biayaPenumpang.numeric' => 'Biaya Penumpang harus hanya berupa angka',
            'biayaMotor.numeric' => 'Biaya Motor harus hanya berupa angka',
            'biayaMobil.numeric' => 'Biaya Mobil harus hanya berupa angka',
            'diskon.numeric' => 'Diskon harus hanya berupa angka',
            'pajak.numeric' => 'Pajak harus hanya berupa angka',
            'kapasitas.numeric' => 'Kapasitas hanya berupa angka'
        ];
    }
}
