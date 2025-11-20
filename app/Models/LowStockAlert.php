<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed $notified_at
 */
class LowStockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'current_stock',
        'threshold',
        'is_resolved',
        'notified_at',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'threshold' => 'integer',
        'is_resolved' => 'boolean',
        'notified_at' => 'datetime',
    ];

    // Relationships
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopePending($query)
    {
        return $query->whereNull('notified_at');
    }
}
