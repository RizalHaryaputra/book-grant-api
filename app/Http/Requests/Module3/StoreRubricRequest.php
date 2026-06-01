<?php

namespace App\Http\Requests\Module3;

use Illuminate\Foundation\Http\FormRequest;

class StoreRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // middleware role:admin sudah menjamin akses
    }

    public function rules(): array
    {
        return [
            'criteria'    => 'required|string|max:255',
            'book_type'   => 'required|in:Buku Ajar,Buku Referensi',
            'description' => 'nullable|string',
            'weight'      => 'required|integer|min:1|max:100',
            'status'      => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'weight.min' => 'Bobot minimal 1.',
            'weight.max' => 'Bobot maksimal 100.',
            'book_type.in' => 'Jenis buku harus Buku Ajar atau Buku Referensi.',
        ];
    }
}