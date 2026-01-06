<?php

namespace App\Http\Requests\Api\V1\Kendaraan;

use Illuminate\Foundation\Http\FormRequest;

class StoreKendaraanRequest extends FormRequest
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
            'checkInCode' => ['required', 'string', 'exists:hardware,code'],
            'idTiket' => ['required', 'exists:tikets,id'],
        ];
    }

    public function messages()
    {
        return [
            'checkInCode.required' => 'Tiket harus melewati pengecekan terlebih dahulu.',
            'checkInCode.exists' => 'Kode pengecekan tidak valid.',
            'idTiket.required' => 'Identitas tiket wajib disertakan.',
            'idTiket.exists' => 'Tiket tidak valid disistem',
        ];
    }
}
