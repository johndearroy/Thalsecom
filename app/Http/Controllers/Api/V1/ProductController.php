<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints"
 * )
 * @method middleware(string $string)
 */
class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Get all products",
     *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Products retrieved successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $products = Product::with('variants')
            ->active()
            ->latest()
            ->paginate($perPage);

        return $this->jsonResponse(
            'Products retrieved successfully',
            200,
            ProductResource::collection($products->items()),
            $products
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreProductRequest")),
     *     @OA\Response(response=201, description="Product created successfully"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $user = auth()->user();

        // Vendors can only create their own products
        $vendorId = $user->isVendor() ? $user->id : $request->input('vendor_id', $user->id);

        $product = $this->productService->createProduct(
            $request->validated(),
            $vendorId
        );

        return $this->jsonResponse(
            'Product created successfully',
            201,
            [new ProductResource($product)],
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Get a specific product",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product retrieved successfully"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function show(Product $product): JsonResponse
    {
        $product->load('variants', 'vendor');

        return $this->jsonResponse(
            'Product retrieved successfully',
            200,
            [new ProductResource($product)],
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Update a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateProductRequest")),
     *     @OA\Response(response=200, description="Product updated successfully"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $user = auth()->user();

        // Authorization: Admin can update any, Vendor can update only their own
        if ($user->isVendor() && $product->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to update this product'
            ], 403);
        }

        $product = $this->productService->updateProduct($product, $request->validated());

        return $this->jsonResponse(
            'Product updated successfully',
            200,
            [new ProductResource($product)],
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product deleted successfully"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function destroy(Product $product): JsonResponse
    {
        $user = auth()->user();

        // Authorization check
        if ($user->isVendor() && $product->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to delete this product'
            ], 403);
        }

        $this->productService->deleteProduct($product);

        return $this->jsonResponse('Product deleted successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/search",
     *     tags={"Products"},
     *     summary="Search products",
     *     @OA\Parameter(name="q", in="query", required=true, description="Search term", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Search results")
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $results = $this->productService->searchProducts(
            $request->input('q'),
            $request->input('per_page', 15)
        );

        return $this->jsonResponse(
            'Search results',
            200,
            ProductResource::collection($results->items()),
            $results,
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products/import",
     *     tags={"Products"},
     *     summary="Import products from CSV",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(@OA\Property(property="file", type="file"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Import completed"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $path = $file->store('temp');
        $fullPath = storage_path('app/' . $path);

        $result = $this->productService->importProductsFromCsv($fullPath);

        // Clean up temp file
        unlink($fullPath);

        return $this->jsonResponse(
            'Import completed',
            201,
            $result,
        );
    }
}
