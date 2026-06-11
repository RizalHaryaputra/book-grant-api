<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $bodyHtml;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $bodyHtml)
    {
        $this->subjectText = $subject;
        $this->bodyHtml = $bodyHtml;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subjectText)
                    ->html($this->bodyHtml); // Langsung render HTML dari NotificationService
    }
}