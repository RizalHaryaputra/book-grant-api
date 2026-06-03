<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function send($to, $subject, $body)
    {
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            return ['success' => true, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Email gagal dikirim: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}