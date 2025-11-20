<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $product_name
 * @property mixed $variant_name
 * @property mixed $price
 * @property mixed $quantity
 * @property mixed $subtotal
 * @method relationLoaded(string $string)
 */
class OrderItemResource extends JsonResource
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
            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'subtotal' => (float) $this->subtotal,
            'product' => $this->when($this->relationLoaded('product'), [
                'id' => $this->product->id ?? null,
                'slug' => $this->product->slug ?? null,
            ]),
        ];
    }
}
