<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Jobs\SendOrderStatusUpdateEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderCancelledNotification implements ShouldQueue
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
    public function handle(OrderCancelled $event): void
    {
        // Send cancellation email
        SendOrderStatusUpdateEmail::dispatch(
            $event->order,
            $event->order->status,
            'cancelled'
        );
    }
}
