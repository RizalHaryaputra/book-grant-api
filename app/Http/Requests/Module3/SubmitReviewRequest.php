<?php

namespace App\Http\Requests\Module3;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'rubric_scores'               => 'required|array',
            'rubric_scores.*.criteria_id' => 'required|integer',
            'rubric_scores.*.score'       => 'required|integer|min:0|max:100',
            'narrative_feedback'          => 'required|string',
        ];
    }
}
