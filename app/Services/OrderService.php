<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Repositories\OrderRepository;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Events\OrderCancelled;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private OrderRepository $orderRepository, private InventoryService $inventoryService)
    {
        //
    }

    /**
     * Create new order and order items, deduct stock
     *
     * @param array $data
     * @param int $customerId
     * @return Order
     */
    public function createOrder(array $data, int $customerId): Order
    {
        return DB::transaction(function () use ($data, $customerId) {
            // Validate stock availability first
            foreach ($data['items'] as $item) {
                $variant = ProductVariant::query()->findOrFail($item['variant_id']);

                if (!$variant->hasStock($item['quantity'])) {
                    throw new \Exception("Insufficient stock for product: {$variant?->name}");
                }
            }

            // Calculate totals
            list($taxAmount, $shippingAmount, $totalAmount) = $this->calculateTotals($data);

            // Create a new order
            $order = $this->createNewOrder($customerId, $totalAmount, $taxAmount, $shippingAmount, $data);

            // Create order items and deduct inventory
            foreach ($data['items'] as $item) {
                $this->createOrderItemsAndDeductStock($order, $item);
            }

            // Fire event for notifications
            event(new OrderCreated($order->fresh(['items', 'customer'])));

            return $order->load(['items.product', 'items.variant']);
        });
    }

    /**
     * Update order status and maintain proper status transition
     * Order status workflow: Pending → Processing → Shipped → Delivered → Cancelled
     *
     * @param Order $order
     * @param string $newStatus
     * @return Order
     * @throws \Exception
     */
    public function updateOrderStatus(Order $order, string $newStatus): Order
    {
        // Validate status transition
        $validTransitions = $this->getValidOrderStatusTransitions();

        $currentStatus = $order->status;

        if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
            throw new \Exception("Invalid status transition from {$currentStatus} to {$newStatus}");
        }

        return DB::transaction(function () use ($order, $newStatus, $currentStatus) {
            // Update status and timestamp
            $updateData = ['status' => $newStatus];

            switch ($newStatus) {
                case Order::STATUS_PROCESSING:
                    $updateData['confirmed_at'] = now();
                    break;
                case Order::STATUS_SHIPPED:
                    $updateData['shipped_at'] = now();
                    break;
                case Order::STATUS_DELIVERED:
                    $updateData['delivered_at'] = now();
                    break;
            }

            $this->orderRepository->update($order, $updateData);

            // Fire event for notifications
            event(new OrderStatusUpdated($order->fresh(), $currentStatus, $newStatus));

            return $order;
        });
    }

    /**
     * @throws \Exception
     */
    public function cancelOrder(Order $order): Order
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception("Order cannot be cancelled in current status: {$order->status}");
        }

        return DB::transaction(function () use ($order) {
            // Restore inventory for all items
            foreach ($order->items as $item) {
                if ($item->variant) {
                    $this->inventoryService->restoreStock(
                        $item->variant,
                        $item->quantity,
                        $order->id,
                        'Order cancelled'
                    );
                }
            }

            // Update order status
            $this->orderRepository->update($order, [
                'status' => Order::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            // Fire event
            event(new OrderCancelled($order->fresh()));

            return $order;
        });
    }

    public function getAllOrders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getAllOrders($perPage);
    }

    public function getCustomerOrders(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getCustomerOrders($customerId, $perPage);
    }

    public function getVendorOrders(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getOrdersForVendor($vendorId, $perPage);
    }

    public function getOrdersByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getOrdersByStatus($status, $perPage);
    }

    /**
     * @throws \Exception
     */
    private function createNewOrder($customerId, $totalAmount, $taxAmount, $shippingAmount, $data): Model
    {
        try {
            return $this->orderRepository->create([
                'user_id' => $customerId,
                'status' => Order::STATUS_PENDING,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function calculateTotals($data): array
    {
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $variant = ProductVariant::query()->find($item['variant_id']);
            $subtotal += $variant->price * $item['quantity'];
        }

        $taxAmount = $data['tax_amount'] ?? ($subtotal * 0.1); // 10% tax if not provided
        $shippingAmount = $data['shipping_amount'] ?? 10.00;
        $totalAmount = $subtotal + $taxAmount + $shippingAmount;
        return [$subtotal, $taxAmount, $shippingAmount, $totalAmount];
    }

    /**
     * @throws \Exception
     */
    private function createOrderItemsAndDeductStock($order, $item): void
    {
        try {
            $variant = ProductVariant::query()->findOrFail($item['variant_id']);

            $order->items()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'price' => $variant->price,
                'quantity' => $item['quantity'],
                'subtotal' => $variant->price * $item['quantity'],
            ]);

            // Deduct inventory
            $this->inventoryService->deductStock(
                $variant,
                $item['quantity'],
                $order->id,
                'Order placed'
            );
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function getValidOrderStatusTransitions(): array
    {
        return [
            Order::STATUS_PENDING => [Order::STATUS_PROCESSING, Order::STATUS_CANCELLED],
            Order::STATUS_PROCESSING => [Order::STATUS_SHIPPED, Order::STATUS_CANCELLED],
            Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED],
            Order::STATUS_DELIVERED => [],
            Order::STATUS_CANCELLED => [],
        ];
    }
}
