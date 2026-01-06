<?php

namespace App\Http\Requests\Api\V1\Penumpang;

use Illuminate\Foundation\Http\FormRequest;

class StorePenumpangRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255', 'unique:penumpangs,email'],
            'nomor_telepon' => ['required', 'string', 'regex:/^\d+$/', 'digits_between:10,15', 'unique:penumpangs,nomor_telepon'],
            'password' => ['required', 'string', 'confirmed'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi',
            'email.unique' => 'Email sudah terdaftar.',
            'email.email' => 'Email harus memiliki format email standar.',
            'nomor_telepon.required' => 'Nomor telepon wajib diisi.',
            'nomor_telepon.unique' => 'Nomor telepon sudah terdaftar.',
            'nomor_telepon.regex' => 'Nomor telepon hanya berupa angka.',
            'nomor_telepon.digits_between' => 'Nomor telepon minimal 10 digit dan maksimal 15 digit.',
            'password.required' => 'Password wajib diisi',
            'password.confirmed' => 'Password dan konfirmasi password tidak sama.',
        ];
    }
}
