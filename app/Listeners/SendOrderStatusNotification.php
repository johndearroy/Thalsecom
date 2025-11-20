<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Jobs\SendOrderStatusUpdateEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderStatusNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        // Send status update email
        SendOrderStatusUpdateEmail::dispatch(
            $event->order,
            $event->oldStatus,
            $event->newStatus
        );
    }
}
