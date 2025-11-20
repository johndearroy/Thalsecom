<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getActiveProducts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->active()
            ->with(['variants' => function ($query) {
                $query->active();
            }])
            ->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->model
            ->where('slug', $slug)
            ->with('variants')
            ->first();
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model
            ->where('sku', $sku)
            ->with('variants')
            ->first();
    }

    public function searchProducts(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->search($term)
            ->active()
            ->with('variants')
            ->paginate($perPage);
    }

    public function getVendorProducts(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $vendorId)
            ->with('variants')
            ->latest()
            ->paginate($perPage);
    }

    public function getLowStockProducts(int $threshold = 10): Collection
    {
        return $this->model
            ->whereHas('variants', function ($query) use ($threshold) {
                $query->where('stock_quantity', '<=', $threshold);
            })
            ->with(['variants' => function ($query) use ($threshold) {
                $query->where('stock_quantity', '<=', $threshold);
            }])
            ->get();
    }

    public function bulkCreate(array $products): bool
    {
        try {
            $this->model->insert($products);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
