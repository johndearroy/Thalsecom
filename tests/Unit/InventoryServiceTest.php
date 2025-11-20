<?php

namespace Tests\Unit;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Repositories\InventoryRepository;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;
    protected ProductVariant $variant;
    protected User $user;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $vendorRole = Role::query()->where('name', 'vendor')->first();
        $this->user = User::query()->create([
            'name' => 'Test User',
            'email' => 'testuser@thalsecom.com',
            'password' => bcrypt('password123'),
            'role_id' => $vendorRole->id,
        ]);

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $subtotal = fake()->randomFloat(2, 50, 500);
        $tax = $subtotal * 0.10;
        $shippingFee = 10.00;

        $this->order = Order::query()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'status' => fake()->randomElement(['pending', 'processing', 'shipped']),
            'tax_amount' => $tax,
            'shipping_amount' => $shippingFee,
            'total_amount' => $subtotal + $tax + $shippingFee,
            'shipping_address' => fake()->address(),
            'billing_address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
        ]);

        $this->variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'TEST-VAR-001',
            'name' => 'Default',
            'price' => 99.99,
            'stock_quantity' => 100,
        ]);

        $this->inventoryService = new InventoryService(new InventoryRepository(new InventoryLog()));
    }

    /**
     * @throws \Exception
     */
    public function test_can_reduce_stock()
    {
        $result = $this->inventoryService->updateStock(
            variantId: $this->variant->id,
            quantity: 10,
            type: 'deduction',
            userId: $this->user->id
        );

        $this->assertTrue($result);
        $this->variant->refresh();
        $this->assertEquals(90, $this->variant->stock_quantity);
    }

    /**
     * @throws \Exception
     */
    public function test_can_increase_stock()
    {
        $result = $this->inventoryService->updateStock(
            variantId: $this->variant->id,
            quantity: 20,
            type: 'addition',
            userId: $this->user->id
        );

        $this->assertTrue($result);
        $this->variant->refresh();
        $this->assertEquals(120, $this->variant->stock_quantity);
    }

    /**
     * @throws \Exception
     */
    public function test_creates_inventory_log()
    {
        $this->inventoryService->updateStock(
            variantId: $this->variant->id,
            quantity: 5,
            type: 'adjustment',
            userId: $this->user->id,
            reason: 'Damaged items'
        );

        $this->assertDatabaseHas('inventory_logs', [
            'product_variant_id' => $this->variant->id,
            'type' => 'adjustment',
            'performed_by' => $this->user->id,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function test_can_reserve_stock_for_order()
    {
        $items = [
            [
                'variant_id' => $this->variant->id,
                'quantity' => 15,
            ]
        ];

        $result = $this->inventoryService->reserveStock($items, $this->order->id, $this->user->id);

        $this->assertTrue($result);
        $this->variant->refresh();
        $this->assertEquals(85, $this->variant->stock_quantity);
    }

    public function test_cannot_reserve_more_than_available_stock()
    {
        $this->expectException(\Exception::class);

        $items = [
            [
                'variant_id' => $this->variant->id,
                'quantity' => 150, // More than available
            ]
        ];

        $this->inventoryService->reserveStock($items, $this->order->id, $this->user->id);
    }

    public function test_can_restore_stock()
    {
        // First reduce stock
        $this->variant->update(['stock_quantity' => 50]);

        $result = $this->inventoryService->restoreStock($this->variant, 10, $this->user->id);

        $this->assertInstanceOf(InventoryLog::class, $result);
        $this->variant->refresh();
        $this->assertEquals(60, $this->variant->stock_quantity);
    }
}
