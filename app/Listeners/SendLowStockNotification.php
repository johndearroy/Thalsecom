<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Jobs\SendLowStockAlertEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLowStockNotification implements ShouldQueue
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
    public function handle(LowStockDetected $event): void
    {
        // Notify vendor about low stock
        SendLowStockAlertEmail::dispatch($event->variant);
    }
}
