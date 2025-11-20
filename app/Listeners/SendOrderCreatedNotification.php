<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\GenerateInvoicePdf;
use App\Jobs\SendOrderConfirmationEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderCreatedNotification implements ShouldQueue
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
    public function handle(OrderCreated $event): void
    {
        // Dispatch email job to queue
        SendOrderConfirmationEmail::dispatch($event->order);

        // Generate invoice PDF in background
        GenerateInvoicePdf::dispatch($event->order);
    }
}
