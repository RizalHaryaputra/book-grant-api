<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PublisherDecision;

class DecisionMade
{
    use Dispatchable, SerializesModels;

    public $publisherDecision;

    /**
     * Create a new event instance.
     */
    public function __construct(PublisherDecision $publisherDecision)
    {
        $this->publisherDecision = $publisherDecision;
    }
}