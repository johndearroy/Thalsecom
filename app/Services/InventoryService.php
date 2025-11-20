<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\InventoryLog;
use App\Models\LowStockAlert;
use App\Jobs\CheckLowStockJob;
use App\Repositories\InventoryRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(private InventoryRepository $inventoryRepository)
    {
        //
    }

    public function deductStock(ProductVariant $variant, int $quantity, ?int $orderId = null, ?string $reason = null): InventoryLog
    {
        return DB::transaction(function () use ($variant, $quantity, $orderId, $reason) {
            $previousStock = $variant->stock_quantity;

            if ($previousStock < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$previousStock}, Required: {$quantity}");
            }

            // Deduct the stock
            $variant->decreaseStock($quantity);
            $newStock = $variant->fresh()->stock_quantity;

            // Log the transaction
            $log = $this->inventoryRepository->create([
                'product_variant_id' => $variant->id,
                'order_id' => $orderId,
                'type' => InventoryLog::TYPE_DEDUCTION,
                'quantity' => -$quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $reason ?? 'Stock deducted',
                'performed_by' => auth()->id(),
            ]);

            // Check for low stock and dispatch job
            dispatch(new CheckLowStockJob($variant->id));

            return $log;
        });
    }

    public function restoreStock(ProductVariant $variant, int $quantity, ?int $orderId = null, ?string $reason = null): InventoryLog
    {
        return DB::transaction(function () use ($variant, $quantity, $orderId, $reason) {
            $previousStock = $variant->stock_quantity;

            // Add back the stock
            $variant->increaseStock($quantity);
            $newStock = $variant->fresh()->stock_quantity;

            // Log the transaction
            $log = $this->inventoryRepository->create([
                'product_variant_id' => $variant->id,
                'order_id' => $orderId,
                'type' => InventoryLog::TYPE_RETURN,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $reason ?? 'Stock restored',
                'performed_by' => auth()->id(),
            ]);

            // Resolve low stock alert if stock is now sufficient
            $this->resolveLowStockAlert($variant);

            return $log;
        });
    }

    public function addStock(ProductVariant $variant, int $quantity, ?string $reason = null): InventoryLog
    {
        return DB::transaction(function () use ($variant, $quantity, $reason) {
            $previousStock = $variant->stock_quantity;

            $variant->increaseStock($quantity);
            $newStock = $variant->fresh()->stock_quantity;

            $log = $this->inventoryRepository->create([
                'product_variant_id' => $variant->id,
                'type' => InventoryLog::TYPE_ADDITION,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $reason ?? 'Stock added',
                'performed_by' => auth()->id(),
            ]);

            $this->resolveLowStockAlert($variant);

            return $log;
        });
    }

    public function adjustStock(
        ProductVariant $variant,
        int $newQuantity,
        ?string $reason = null
    ): InventoryLog {
        return DB::transaction(function () use ($variant, $newQuantity, $reason) {
            $previousStock = $variant->stock_quantity;
            $difference = $newQuantity - $previousStock;

            $variant->update(['stock_quantity' => $newQuantity]);

            $log = $this->inventoryRepository->create([
                'product_variant_id' => $variant->id,
                'type' => InventoryLog::TYPE_ADJUSTMENT,
                'quantity' => $difference,
                'previous_stock' => $previousStock,
                'new_stock' => $newQuantity,
                'reason' => $reason ?? 'Stock adjusted',
                'performed_by' => auth()->id(),
            ]);

            // Check stock level
            if ($newQuantity > 10) {
                $this->resolveLowStockAlert($variant);
            } else {
                dispatch(new CheckLowStockJob($variant->id));
            }

            return $log;
        });
    }

    public function checkLowStock(ProductVariant $variant, int $threshold = 10): ?LowStockAlert
    {
        if ($variant->isLowStock($threshold)) {
            // Create or update alert
            return LowStockAlert::query()->updateOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'is_resolved' => false,
                ],
                [
                    'current_stock' => $variant->stock_quantity,
                    'threshold' => $threshold,
                ]
            );
        }

        return null;
    }

    public function resolveLowStockAlert(ProductVariant $variant): void
    {
        LowStockAlert::query()->where('product_variant_id', $variant->id)
            ->where('is_resolved', false)
            ->update(['is_resolved' => true]);
    }

    public function getInventoryLogs(ProductVariant $variant, int $perPage = 15): LengthAwarePaginator
    {
        return InventoryLog::query()->where('product_variant_id', $variant->id)
            ->with(['order', 'performer'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Update stock quantity with type and logging
     *
     * @param int $variantId
     * @param int $quantity
     * @param string $type (addition, deduction, adjustment, return)
     * @param int $userId
     * @param string|null $reason
     * @return bool
     * @throws \Exception
     */
    public function updateStock(int $variantId, int $quantity, string $type, int $userId, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($variantId, $quantity, $type, $userId, $reason) {
            $variant = ProductVariant::query()->findOrFail($variantId);

            $previousStock = $variant->stock_quantity;
            $newStock = $previousStock;
            $logQuantity = $quantity;

            // Calculate new stock based on type
            switch ($type) {
                case 'deduction':
                    if ($previousStock < $quantity) {
                        throw new \Exception("Insufficient stock. Available: {$previousStock}, Required: {$quantity}");
                    }
                    $newStock = $previousStock - $quantity;
                    $logQuantity = -$quantity;
                    break;

                case 'addition':
                case 'return':
                    $newStock = $previousStock + $quantity;
                    $logQuantity = $quantity;
                    break;

                case 'adjustment':
                    // For adjustment, quantity is the new stock value
                    $newStock = $quantity;
                    $logQuantity = $quantity - $previousStock;
                    break;

                default:
                    throw new \Exception("Invalid type: {$type}");
            }

            // Update variant stock
            $variant->update(['stock_quantity' => $newStock]);

            // Create inventory log
            $this->inventoryRepository->create([
                'product_variant_id' => $variantId,
                'type' => $type,
                'quantity' => $logQuantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $reason,
                'performed_by' => $userId,
            ]);

            return true;
        });
    }

    /**
     * Reserve stock for order items
     *
     * @param array $items [['variant_id' => 1, 'quantity' => 5], ...]
     * @param int $orderId
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function reserveStock(array $items, int $orderId, int $userId): bool
    {
        return DB::transaction(function () use ($items, $orderId, $userId) {
            // Validate all items have sufficient stock first
            foreach ($items as $item) {
                $variant = ProductVariant::query()->findOrFail($item['variant_id']);

                if ($variant->stock_quantity < $item['quantity']) {
                    throw new \Exception(
                        "Insufficient stock for variant {$variant->sku}. "
                        . "Available: {$variant->stock_quantity}, Required: {$item['quantity']}"
                    );
                }
            }

            // Reserve stock for each item
            foreach ($items as $item) {
                $variant = ProductVariant::query()->find($item['variant_id']);
                $previousStock = $variant->stock_quantity; // 100
                $newStock = $previousStock - $item['quantity']; // 85

                // Update variant stock
                $variant->update(['stock_quantity' => $newStock]);

                // Create inventory log with order_id
                $this->inventoryRepository->create([
                    'product_variant_id' => $item['variant_id'],
                    'order_id' => $orderId,
                    'type' => 'deduction',
                    'quantity' => -$item['quantity'],
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reason' => "Reserved for order #{$orderId}",
                    'performed_by' => $userId,
                ]);
            }

            return true;
        });
    }
}
