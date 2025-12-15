<?php

namespace App\Http\Requests\Api\V1\Tiket;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Api\V1\Tiket\ValidasiWaktuBerangkat;
use App\Rules\Api\V1\Tiket\ValidasiKapasitasJadwal;
use App\Rules\Api\V1\Tiket\ValidasiJumlahPenumpang;

class UpdateTiketRequest extends FormRequest
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
            'penumpangId' => ['sometimes', 'exists:penumpangs,id'],
            'jadwalId' => ['sometimes', 'exists:jadwals,id', new ValidasiWaktuBerangkat, new ValidasiKapasitasJadwal],
            'penumpangList' => ['sometimes', 'array', new ValidasiJumlahPenumpang],
            'penumpangList.*' => ['sometimes', 'string'],
            'nomorKendaraan' => ['sometimes', 'string'],
            'jenisKendaraan' => ['sometimes', 'in:mobil,motor'],
            'biayaTiket' => ['sometimes', 'numeric'],
            'status' => ['sometimes', 'in:menunggu_verifikasi,terverifikasi,dibatalkan'],
        ];
    }

    public function messages()
    {
        return [
            'penumpangId.exists' => 'ID Penumpang Tidak Ditemukan',
            'jadwalId.exists' => 'ID Jadwal Tidak Ditemukan',
            'penumpangList.array' => 'Penumpang List harus Array',
            'nomorKendaraan.string' => 'Nomor Kendaraan harus String',
            'jenisKendaraan.in' => 'Jenis Kendaraan Tidak Valid',
            'biayaTiket.numeric' => 'Biaya Tiket harus Numeric',
            'status.in' => 'Status Tidak Valid',
        ];
    }
}
