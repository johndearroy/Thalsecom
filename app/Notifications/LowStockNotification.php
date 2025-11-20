<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public ProductVariant $variant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low Stock Alert')
            ->line("The product variant '{$this->variant->variant_name}' is running low on stock.")
            ->line("Current stock: {$this->variant->stock_quantity}")
            ->line("Product: {$this->variant->product->name}")
            ->line('Please restock soon to avoid stockouts.')
            ->action('View Product', url("/products/{$this->variant->product->id}"));
    }
}
