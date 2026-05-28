<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Set true karena internal, atau sesuaikan dengan role
    }

    public function rules()
    {
        return [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string|in:account_created,contract_validated,draft_uploaded,review_assigned,review_completed,revision_requested,preprint_entered,publisher_approved,publisher_revised,deadline_reminder'
        ];
    }

    public function messages()
    {
        return [
            'to.required' => 'Email penerima wajib diisi',
            'to.email' => 'Format email tidak valid',
            'subject.required' => 'Subjek email wajib diisi',
            'body.required' => 'Isi email wajib diisi',
            'type.required' => 'Tipe notifikasi wajib diisi',
            'type.in' => 'Tipe notifikasi tidak valid'
        ];
    }
}