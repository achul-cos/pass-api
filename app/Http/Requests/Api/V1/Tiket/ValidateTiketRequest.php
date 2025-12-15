<?php

namespace App\Http\Requests\Api\V1\Tiket;

use App\Rules\Api\V1\Tiket\Scan\ValidasiWaktuDatang;
use Illuminate\Foundation\Http\FormRequest;

class ValidateTiketRequest extends FormRequest
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
            'kodeUnik' => ['required', 'string', 'exists:tikets,kode_unik'],
            'nomorKendaraan' => ['required', 'string'],
            'waktuDatang' => ['required', 'date'],
        ];
    }

    public function messages()
    {
        return [
            'kodeUnik.required' => 'Kode Unik Tidak Boleh Kosong',
            'nomorKendaraan.required' => 'Nomor Kendaraan Tidak Boleh Kosong',
            'waktuDatang.required' => 'Waktu Datang Tidak Boleh Kosong',
            'kodeUnik.exists' => 'Kode Unik Tiket Tidak Valid',
            'waktuDatang.date' => 'Waktu Datang Harus Berupa Waktu',
        ];
    }
}
