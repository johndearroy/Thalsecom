<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(private ProductRepository $productRepository)
    {
        //
    }

    /**
     * Create product with variants
     *
     * @param array $data
     * @param int $vendorId
     * @return Product
     */
    public function createProduct(array $data, int $vendorId): Product
    {
        return DB::transaction(function () use ($data, $vendorId) {
            // Create the main product
            $productData = $this->prepareProductData($data, $vendorId);

            $product = $this->productRepository->create($productData);

            // Create variants if provided
            if (isset($data['variants']) && is_array($data['variants'])) {
                foreach ($data['variants'] as $variant) {
                    $variantData = $this->prepareVariantData($variant);
                    $product->variants()->create($variantData);
                }
            }

            return $product->load('variants');
        });
    }

    /**
     * Update product with variants
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            // Update product basic info
            $this->updateProductBasicInfo($product, $data);

            // Update variants if provided
            if (isset($data['variants']) && is_array($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    if (isset($variantData['id'])) {
                        // Update existing variant
                        $variant = ProductVariant::find($variantData['id']);
                        if ($variant && $variant->product_id === $product->id) {
                            $variant->update($variantData);
                        }
                    } else {
                        // Create new variant
                        $product->variants()->create($variantData);
                    }
                }
            }

            return $product->fresh(['variants']);
        });
    }

    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Soft delete the product (variants will cascade)
            return $this->productRepository->delete($product);
        });
    }

    public function searchProducts(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->searchProducts($term, $perPage);
    }

    /**
     * Import products from a CSV file
     * A sample CSV file is in app/storage/samples/products_import_sample.csv
     *
     * @param string $filePath
     * @return array
     */
    public function importProductsFromCsv(string $filePath): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'File not found',
            ];
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Skip header row

        // Validate required columns
        $requiredColumns = ['name', 'sku', 'base_price', 'description', 'vendor'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $header)) {
                fclose($file);
                return [
                    'success' => false,
                    'message' => "Missing required column: {$column}",
                ];
            }
        }

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file)) !== false) {
                try {
                    $vendor = User::query()->where('name', $row[4])->firstOrFail();
                    if (!$vendor) {
                        $failed++;
                        $errors[] = "No vendor found with the name: {$row[4]}";
                        continue;
                    }
                    // Map CSV columns to product data
                    $productData = [
                        'name' => $row[0],
                        'description' => $row[1] ?? null,
                        'base_price' => $row[2],
                        'sku' => $row[3],
                        'user_id' => $vendor?->id,
                    ];

                    // Basic validation
                    if (empty($productData['name']) || empty($productData['sku'])) {
                        $failed++;
                        $errors[] = "Invalid data for SKU: {$row[3]}";
                        continue;
                    }

                    $this->productRepository->create($productData);
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Error importing SKU {$row[3]}: " . $e->getMessage();
                }
            }

            DB::commit();
            fclose($file);

            return [
                'success' => true,
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);

            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare data to create a Product
     * @param array $data
     * @param int $vendorId
     * @return array
     */
    private function prepareProductData(array $data, int $vendorId): array
    {
        return [
            'user_id' => $vendorId,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'base_price' => $data['base_price'],
            'sku' => $data['sku'],
            'is_active' => $data['is_active'] ?? true,
        ];
    }

    /**
     * Prepare data to create a associate product variant
     * @param array $variant
     * @return array
     */
    private function prepareVariantData(array $variant): array
    {
        return [
            'name' => $variant['name'],
            'sku' => $variant['sku'],
            'price' => $variant['price'],
            'stock_quantity' => $variant['stock_quantity'] ?? 0,
            'attributes' => $variant['attributes'] ?? null,
            'is_active' => $variant['is_active'] ?? true,
        ];
    }

    /**
     * Update product basic information's
     * @throws \Exception
     */
    private function updateProductBasicInfo(Product $product, array $data): void
    {
        try {
            $this->productRepository->update($product, [
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'base_price' => $data['base_price'] ?? $product->base_price,
                'sku' => $data['sku'] ?? $product->sku,
                'is_active' => $data['is_active'] ?? $product->is_active,
            ]);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
