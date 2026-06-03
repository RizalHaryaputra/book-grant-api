<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PublisherCheck;

class DecisionMade
{
    use Dispatchable, SerializesModels;

    public $publisherCheck;
    public $revisionNotes;

    public function __construct(PublisherCheck $publisherCheck, $revisionNotes = null)
    {
        $this->publisherCheck = $publisherCheck;
        $this->revisionNotes = $revisionNotes;
    }
}