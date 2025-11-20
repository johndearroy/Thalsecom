<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $name
 * @property mixed $slug
 * @property mixed $description
 * @property mixed $base_price
 * @property mixed $sku
 * @property mixed $is_active
 * @property mixed $created_at
 * @property mixed $updated_at
 * @method relationLoaded(string $string)
 * @method getTotalStock()
 */
class ProductResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'base_price' => (float) $this->base_price,
            'sku' => $this->sku,
            'is_active' => (bool) $this->is_active,
            'vendor' => $this->when($this->relationLoaded('vendor'), [
                'id' => $this->vendor->id ?? null,
                'name' => $this->vendor->name ?? null,
            ]),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'total_stock' => $this->when($this->relationLoaded('variants'), function () {
                return $this->getTotalStock();
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
