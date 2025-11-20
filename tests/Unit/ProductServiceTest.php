<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use App\Repositories\ProductRepository;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $productService;
    protected User $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $vendorRole = Role::query()->where('name', 'vendor')->first();
        $this->vendor = User::query()->create([
            'name' => 'Test Vendor',
            'email' => 'testvendor@thalsecom.com',
            'password' => bcrypt('password123'),
            'role_id' => $vendorRole->id,
        ]);

        $this->productService = new ProductService(
            new ProductRepository(new Product())
        );
    }

    public function test_can_create_product_with_variants()
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'base_price' => 99.99,
            'sku' => 'TEST-SKU-001',
            'variants' => [
                [
                    'sku' => 'VAR-001',
                    'name' => 'Small',
                    'price' => 89.99,
                    'stock_quantity' => 50,
                ]
            ]
        ];

        $product = $this->productService->createProduct($productData, $this->vendor->id);

        $this->assertNotNull($product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertCount(1, $product->variants);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create([
            'user_id' => $this->vendor->id,
            'name' => 'Original Name',
        ]);

        $updatedProduct = $this->productService->updateProduct($product, [
            'name' => 'Updated Name',
        ]);

        $this->assertEquals('Updated Name', $updatedProduct->name);
    }

    public function test_can_search_product()
    {
        $product = Product::factory()->create([
            'user_id' => $this->vendor->id,
            'name' => 'Original Name',
        ]);

        $isFound = $this->productService->searchProducts('Original');

        $this->assertInstanceOf(LengthAwarePaginator::class, $isFound);
        $this->assertEquals(1, $isFound->currentPage());
        $this->assertEquals(15, $isFound->perPage());
        $this->assertEquals(1, $isFound->total());
        $this->assertCount(1, $isFound->items());
        $this->assertInstanceOf(Product::class, $isFound->items()[0]);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create([
            'user_id' => $this->vendor->id,
            'name' => 'Original Name',
        ]);

        $isDeleted = $this->productService->deleteProduct($product);

        $this->assertTrue($isDeleted);

        $this->assertSoftDeleted($product);
    }

    public function test_can_import_products()
    {
        $vendorRole = Role::query()->where('name', 'vendor')->first();
        // Creating only one vendor
        User::query()->create([
            'name' => 'Vendor One',
            'email' => 'testvendor1@thalsecom.com',
            'password' => bcrypt('password123'),
            'role_id' => $vendorRole->id,
        ]);
        $filePath = storage_path('app/samples/products_import_sample.csv');

        $result = $this->productService->importProductsFromCsv($filePath);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['imported']);
        $this->assertEquals(10, $result['failed']);
        $this->assertCount(10, $result['errors']);

        $this->assertDatabaseHas('products', ['sku' => 'LAPTOP-GAMING-001']);
        $this->assertDatabaseMissing('products', ['sku' => 'MONITOR-27-001']); // Belongs to Vendor two which might not imported
    }
}
