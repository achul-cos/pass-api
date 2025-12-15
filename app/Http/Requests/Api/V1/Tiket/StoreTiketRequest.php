<?php

namespace App\Http\Requests\Api\V1\Tiket;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Api\V1\Tiket\ValidasiWaktuBerangkat;
use App\Rules\Api\V1\Tiket\ValidasiKapasitasJadwal;
use App\Rules\Api\V1\Tiket\ValidasiJumlahPenumpang;

class StoreTiketRequest extends FormRequest
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
            'penumpangId' => ['required', 'exists:penumpangs,id'],
            'jadwalId' => ['required', 'exists:jadwals,id', new ValidasiWaktuBerangkat, new ValidasiKapasitasJadwal],
            'penumpangList' => ['required', 'array', new ValidasiJumlahPenumpang],
            'penumpangList.*' => ['required', 'string'],
            'nomorKendaraan' => ['required', 'string'],
            'jenisKendaraan' => ['required', 'in:mobil,motor'],
        ];
    }

    public function messages()
    {
        return [
            'penumpangId.required' => 'ID Penumpang Wajib Diisi',
            'jadwalId.required' => 'ID Jadwal Wajib Diisi',
            'penumpangList.required' => 'Penumpang List Wajib Diisi',
            'nomorKendaraan.required' => 'Nomor Kendaraan Wajib Diisi',
            'jenisKendaraan.required' => 'Jenis Kendaraan Wajib Diisi',
            'penumpangId.exists' => 'ID Penumpang Tidak Ditemukan',
            'jadwalId.exists' => 'ID Jadwal Tidak Ditemukan',
            'penumpangList.array' => 'Penumpang List harus Array',
            'nomorKendaraan.string' => 'Nomor Kendaraan harus String',
            'jenisKendaraan.in' => 'Jenis Kendaraan Tidak Valid',
        ];
    }
}
