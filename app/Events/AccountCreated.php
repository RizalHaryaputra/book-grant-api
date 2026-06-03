<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AccountCreated Event
 *
 * Fired by Modul 1 (Accounts & Contracts) when a new user account is
 * successfully registered. Modul 4 (Notifications) listens to this event
 * to send a welcome / account-creation email to the new user.
 *
 * This event acts as a decoupled integration contract between modules,
 * allowing Modul 4 to operate independently of Modul 1's implementation.
 *
 * Expected payload keys:
 *   - user_id  (int)    The ID of the newly created user.
 *   - email    (string) The user's email address.
 *   - name     (string) The user's full name.
 */
class AccountCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The account payload from Modul 1.
     *
     * @var array{user_id: int, email: string, name: string}
     */
    public readonly array $payload;

    /**
     * Create a new event instance.
     *
     * @param  array{user_id: int, email: string, name: string}  $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
