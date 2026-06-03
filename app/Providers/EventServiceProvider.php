<?php

namespace App\Providers;

use App\Events\AccountCreated;
use App\Events\ContractValidated;
use App\Events\DecisionMade;
use App\Listeners\SendAccountCreationNotification;
use App\Listeners\SendContractValidationNotification;
use App\Listeners\SendDecisionNotification;
use App\Listeners\UpdateManuscriptStatus;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ----------------------------------------------------------------
        // Modul 4 Internal: Publisher decision → update status & notify
        // ----------------------------------------------------------------
        DecisionMade::class => [
            UpdateManuscriptStatus::class,
            SendDecisionNotification::class,
        ],

        // ----------------------------------------------------------------
        // Modul 1 Integration Contracts (Decoupled Event-Driven)
        // These events are fired by Modul 1 (Accounts & Contracts).
        // Modul 4 listens and handles all notification side-effects.
        // ----------------------------------------------------------------
        AccountCreated::class => [
            SendAccountCreationNotification::class,
        ],

        ContractValidated::class => [
            SendContractValidationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}