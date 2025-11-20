<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $order_number
 * @property mixed $status
 * @property mixed $total_amount
 * @property mixed $tax_amount
 * @property mixed $shipping_amount
 * @property mixed $shipping_address
 * @property mixed $billing_address
 * @property mixed $notes
 * @property mixed $confirmed_at
 * @property mixed $shipped_at
 * @property mixed $delivered_at
 * @property mixed $cancelled_at
 * @property mixed $created_at
 * @property mixed $updated_at
 * @method relationLoaded(string $string)
 */
class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'tax_amount' => (float) $this->tax_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'notes' => $this->notes,
            'customer' => $this->when($this->relationLoaded('customer'), [
                'id' => $this->customer->id ?? null,
                'name' => $this->customer->name ?? null,
                'email' => $this->customer->email ?? null,
            ]),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
