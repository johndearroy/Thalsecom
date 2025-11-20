<?php

namespace App\Jobs;

use App\Mail\OrderStatusUpdateMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusUpdateEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {
    }

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        try {
            Log::info('Start sending order status update email');

            Mail::to($this->order->customer->email)
                ->send(new OrderStatusUpdateMail(
                    $this->order,
                    $this->oldStatus,
                    $this->newStatus
                ));

            Log::info('End sending order status update email');
        } catch (\Exception $e) {
            Log::error('Failed to send order status update email', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
