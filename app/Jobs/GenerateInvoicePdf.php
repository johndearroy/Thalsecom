<?php

namespace App\Jobs;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public Order $order)
    {
    }

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        try {
            // Load order with relationships
            $this->order->load(['items.product', 'items.variant', 'customer']);

            // Generate PDF from view
            $pdf = Pdf::loadView('invoices.order', [
                'order' => $this->order
            ]);

            // Define storage path
            $filename = "invoices/order-{$this->order->order_number}.pdf";

            // Save to storage
            Storage::put($filename, $pdf->output());

            Log::info('Invoice PDF generated successfully', [
                'order_id' => $this->order->id,
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate invoice PDF', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
