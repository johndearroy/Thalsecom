<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $name
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // Role constants for easy reference
    public const ADMIN = 'admin';
    public const VENDOR = 'vendor';
    public const CUSTOMER = 'customer';

    /**
     * Get all users with this role
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role is admin
     */
    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    /**
     * Check if role is vendor
     */
    public function isVendor(): bool
    {
        return $this->name === self::VENDOR;
    }

    /**
     * Check if role is customer
     */
    public function isCustomer(): bool
    {
        return $this->name === self::CUSTOMER;
    }
}
