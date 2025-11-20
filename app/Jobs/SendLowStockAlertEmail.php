<?php

namespace App\Jobs;

use App\Mail\LowStockAlertMail;
use App\Models\ProductVariant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLowStockAlertEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public ProductVariant $variant)
    {
    }

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        try {
            Log::info('Start sending low stock alert email');
            // Get the vendor email
            $vendorEmail = $this->variant?->product?->vendor?->email;

            Mail::to($vendorEmail)->send(new LowStockAlertMail($this->variant));

            // Update the alert notification timestamp
            $this->variant->lowStockAlerts()
                ->unresolved() // Scope of LowStockAlert where is_resolved = false
                ->update(['notified_at' => now()]);

            Log::info('End sending low stock alert email');
        } catch (\Exception $e) {
            Log::error('Failed to send low stock alert', [
                'variant_id' => $this->variant->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
