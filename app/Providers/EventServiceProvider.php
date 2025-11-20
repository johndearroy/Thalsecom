<?php

namespace App\Providers;

use App\Events\LowStockDetected;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Listeners\SendLowStockNotification;
use App\Listeners\SendOrderCancelledNotification;
use App\Listeners\SendOrderCreatedNotification;
use App\Listeners\SendOrderStatusNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderCreated::class => [
            SendOrderCreatedNotification::class,
        ],
        OrderStatusUpdated::class => [
            SendOrderStatusNotification::class,
        ],
        OrderCancelled::class => [
            SendOrderCancelledNotification::class,
        ],
        LowStockDetected::class => [
            SendLowStockNotification::class,
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
