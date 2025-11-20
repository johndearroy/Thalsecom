<?php

namespace App\Jobs;

use App\Events\LowStockDetected;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $variantId)
    {
    }

    public function handle(InventoryService $inventoryService): void
    {
        try {
            Log::info('Start checking low stock status');

            $variant = ProductVariant::query()->find($this->variantId);
            Log::info('Checking low stock status of ' . json_encode($variant));

            if (!$variant) {
                return;
            }

            $threshold = 10; // Can be made configurable

            $alert = $inventoryService->checkLowStock($variant, $threshold);
            Log::info('Alerts: ' . json_encode($alert));

            // If alert was created and not yet notified, fire event
            if ($alert && !$alert->notified_at) {
                Log::info('Sending low stock alert');
                event(new LowStockDetected($variant, $variant->stock_quantity, $threshold));
            }

            Log::info('End checking low stock status');
        } catch (\Exception $e) {
            Log::error('Failed to check low stock', [
                'variant_id' => $this->variantId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
