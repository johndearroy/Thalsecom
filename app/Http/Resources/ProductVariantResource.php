<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $product_id
 * @property mixed $name
 * @property mixed $sku
 * @property mixed $price
 * @property mixed $stock_quantity
 * @property mixed $attributes
 * @property mixed $is_active
 * @property mixed $created_at
 * @method isLowStock()
 */
class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'stock_quantity' => $this->stock_quantity,
            'attributes' => $this->attributes,
            'is_active' => (bool) $this->is_active,
            'is_low_stock' => $this->isLowStock(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
