<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $status
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Order {$this->order->order_number} Status Update")
            ->line("Your order status has been updated to: " . ucfirst($this->status));

        switch ($this->status) {
            case Order::STATUS_PROCESSING:
                $message->line('Your order is being prepared for shipment.');
                break;
            case Order::STATUS_SHIPPED:
                $message->line('Your order has been shipped and is on its way!');
                break;
            case Order::STATUS_DELIVERED:
                $message->line('Your order has been delivered. Thank you for shopping with us!');
                break;
            case Order::STATUS_CANCELLED:
                $message->line('Your order has been cancelled. If you have any questions, please contact us.');
                break;
        }

        return $message->action('View Order', url("/orders/{$this->order->id}"));
    }
}
