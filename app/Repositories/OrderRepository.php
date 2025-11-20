<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function getAllOrders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model::with(['items', 'customer'])
                ->latest()
                ->paginate($perPage);
    }

    public function getCustomerOrders(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->forCustomer($customerId) // forCustomer is a scope in Order model
            ->with(['items.product', 'items.variant'])
            ->recent()
            ->paginate($perPage);
    }

    public function findWithItems(int $id): Collection|Model
    {
        return $this->model
            ->with(['items.product', 'items.variant', 'customer'])
            ->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->model
            ->where('order_number', $orderNumber)
            ->with(['items.product', 'items.variant'])
            ->first();
    }

    public function getOrdersByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->status($status)
            ->with(['items', 'customer'])
            ->recent()
            ->paginate($perPage);
    }

    public function getRecentOrders(int $days = 30): Collection
    {
        return $this->model
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['items', 'customer'])
            ->recent()
            ->get();
    }

    public function getPendingOrders(): Collection
    {
        return $this->model
            ->status(Order::STATUS_PENDING)
            ->with(['items'])
            ->get();
    }

    public function getOrdersForVendor(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereHas('items.product', function ($query) use ($vendorId) {
                $query->where('user_id', $vendorId);
            })
            ->with(['items' => function ($query) use ($vendorId) {
                $query->whereHas('product', function ($q) use ($vendorId) {
                    $q->where('user_id', $vendorId);
                });
            }, 'customer'])
            ->recent()
            ->paginate($perPage);
    }
}
