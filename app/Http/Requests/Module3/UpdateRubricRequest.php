<?php

namespace App\Http\Requests\Module3;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'criteria'    => 'sometimes|required|string|max:255',
            'book_type'   => 'sometimes|required|in:Buku Ajar,Buku Referensi',
            'description' => 'nullable|string',
            'weight'      => 'sometimes|required|integer|min:1|max:100',
            'status'      => 'sometimes|boolean',
        ];
    }
}