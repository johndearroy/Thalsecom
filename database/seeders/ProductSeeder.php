<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get vendor users
        $vendors = User::query()->whereHas('role', fn($q) => $q->where('name', User::ROLE_VENDOR))->get();

        if ($vendors->isEmpty()) {
            $this->command->warn('No vendors found. Please run UserSeeder first.');
            return;
        }

        // Create products for each vendor
        foreach ($vendors as $vendor) {
            // Laptop product
            $laptop = Product::query()->create([
                'user_id' => $vendor->id,
                'name' => 'Gaming Laptop Pro',
                'slug' => 'gaming-laptop-pro-' . $vendor->id,
                'description' => 'High performance gaming laptop with latest specs',
                'base_price' => 1299.99,
                'sku' => 'LAPTOP-001-' . $vendor->id,
                'is_active' => true,
            ]);

            // Add variants for laptop
            ProductVariant::query()->create([
                'product_id' => $laptop->id,
                'name' => '16GB RAM, 512GB SSD',
                'sku' => 'LAPTOP-001-V1-' . $vendor->id,
                'price' => 1299.99,
                'stock_quantity' => 50,
                'attributes' => ['ram' => '16GB', 'storage' => '512GB'],
                'is_active' => true,
            ]);

            ProductVariant::query()->create([
                'product_id' => $laptop->id,
                'name' => '32GB RAM, 1TB SSD',
                'sku' => 'LAPTOP-001-V2-' . $vendor->id,
                'price' => 1599.99,
                'stock_quantity' => 30,
                'attributes' => ['ram' => '32GB', 'storage' => '1TB'],
                'is_active' => true,
            ]);

            // T-Shirt product
            $tshirt = Product::query()->create([
                'user_id' => $vendor->id,
                'name' => 'Classic Cotton T-Shirt',
                'slug' => 'classic-tshirt-' . $vendor->id,
                'description' => 'Comfortable 100% cotton t-shirt',
                'base_price' => 19.99,
                'sku' => 'TSHIRT-001-' . $vendor->id,
                'is_active' => true,
            ]);

            // Add size variants for t-shirt
            $sizes = ['S', 'M', 'L', 'XL'];
            foreach ($sizes as $index => $size) {
                ProductVariant::query()->create([
                    'product_id' => $tshirt->id,
                    'name' => "Size {$size}",
                    'sku' => "TSHIRT-001-{$size}-" . $vendor->id,
                    'price' => 19.99,
                    'stock_quantity' => rand(5, 100),
                    'attributes' => ['size' => $size],
                    'is_active' => true,
                ]);
            }

            // Wireless Mouse product with low stock for testing
            $mouse = Product::query()->create([
                'user_id' => $vendor->id,
                'name' => 'Wireless Gaming Mouse',
                'slug' => 'wireless-mouse-' . $vendor->id,
                'description' => 'Ergonomic wireless mouse with RGB lighting',
                'base_price' => 49.99,
                'sku' => 'MOUSE-001-' . $vendor->id,
                'is_active' => true,
            ]);

            ProductVariant::query()->create([
                'product_id' => $mouse->id,
                'name' => 'Black',
                'sku' => 'MOUSE-001-BLACK-' . $vendor->id,
                'price' => 49.99,
                'stock_quantity' => 5, // Low stock for testing alerts
                'attributes' => ['color' => 'black'],
                'is_active' => true,
            ]);

            ProductVariant::query()->create([
                'product_id' => $mouse->id,
                'name' => 'White',
                'sku' => 'MOUSE-001-WHITE-' . $vendor->id,
                'price' => 54.99,
                'stock_quantity' => 80,
                'attributes' => ['color' => 'white'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Products and variants created successfully!');
    }
}
