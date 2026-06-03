<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ContractValidated Event
 *
 * Fired by Modul 1 (Accounts & Contracts) when a publishing contract
 * for an author's manuscript has been successfully validated.
 * Modul 4 (Notifications) listens to this event to send a contract
 * confirmation email to the author.
 *
 * This event acts as a decoupled integration contract between modules,
 * allowing Modul 4 to operate independently of Modul 1's implementation.
 *
 * Expected payload keys:
 *   - user_id           (int)    The ID of the contract owner (author).
 *   - email             (string) The author's email address.
 *   - name              (string) The author's full name.
 *   - manuscript_title  (string) The title of the validated manuscript.
 */
class ContractValidated
{
    use Dispatchable, SerializesModels;

    /**
     * The contract validation payload from Modul 1.
     *
     * @var array{user_id: int, email: string, name: string, manuscript_title: string}
     */
    public readonly array $payload;

    /**
     * Create a new event instance.
     *
     * @param  array{user_id: int, email: string, name: string, manuscript_title: string}  $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
