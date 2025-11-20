<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Info(
 *     title="Thalsecom eCommerce API",
 *     version="1.0.0",
 *     description="eCommerce API with JWT authentication, inventory management, product management, and order processing",
 *     @OA\Contact(
 *         email="admin@thalsecom.com"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost:8001",
 *     description="Local Development Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Schema(
 *      schema="LoginRequest",
 *      required={"email", "password"},
 *      @OA\Property(property="email", type="string", example="example@thalsecom.com"),
 *      @OA\Property(property="password", type="string", example="password"),
 *  )
 * @OA\Schema(
 *       schema="RegisterRequest",
 *       required={"name", "email", "password"},
 *       @OA\Property(property="name", type="string", example="Dear Roy"),
 *       @OA\Property(property="email", type="string", example="example@thalsecom.com"),
 *       @OA\Property(property="password", type="string", example="password"),
 *   )
 * @OA\Schema(
 *     schema="StoreProductRequest",
 *     required={"name", "base_price", "sku"},
 *     @OA\Property(property="name", type="string", example="Gaming Laptop"),
 *     @OA\Property(property="description", type="string", example="High performance laptop"),
 *     @OA\Property(property="base_price", type="number", format="float", example=1299.99),
 *     @OA\Property(property="sku", type="string", example="LAPTOP-001"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(
 *         property="variants",
 *         type="array",
 *         @OA\Items(
 *             @OA\Property(property="name", type="string", example="16GB RAM"),
 *             @OA\Property(property="sku", type="string", example="LAPTOP-001-V1"),
 *             @OA\Property(property="price", type="number", format="float", example=1299.99),
 *             @OA\Property(property="stock_quantity", type="integer", example=50),
 *             @OA\Property(property="attributes", type="object", example={"ram": "16GB", "storage": "512GB"})
 *         )
 *     )
 * )
 * @OA\Schema(
 *     schema="UpdateProductRequest",
 *     @OA\Property(property="name", type="string", example="Gaming Laptop Pro"),
 *     @OA\Property(property="description", type="string", example="Updated description"),
 *     @OA\Property(property="base_price", type="number", format="float", example=1399.99),
 *     @OA\Property(property="is_active", type="boolean", example=true)
 * )
 * @OA\Schema(
 *     schema="StoreOrderRequest",
 *     required={"shipping_address", "items"},
 *     @OA\Property(property="shipping_address", type="string", example="123 Main St, City, State 12345"),
 *     @OA\Property(property="billing_address", type="string", example="123 Main St, City, State 12345"),
 *     @OA\Property(property="notes", type="string", example="Please deliver in the morning"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(
 *             @OA\Property(property="variant_id", type="integer", example=1),
 *             @OA\Property(property="quantity", type="integer", example=2)
 *         )
 *     )
 * )
 */
abstract class Controller
{
    /**
     * This is a custom generic response helper
     * @param string $message
     * @param int|null $statusCode
     * @param array|Collection|ResourceCollection|null $data
     * @param LengthAwarePaginator|null $model
     * @return JsonResponse
     */
    public function jsonResponse(
        string $message,
        ?int $statusCode = 200,
        array|Collection|ResourceCollection|null $data = [],
        ?LengthAwarePaginator $model = null // for pagination
    ): JsonResponse
    {
        $response = [
            'message' => $message,
            'status_code' => $statusCode,
            'data' => $data
        ];
        if ($model) {
            $response['meta'] = [
                'current_page' => $model->currentPage(),
                'last_page' => $model->lastPage(),
                'per_page' => $model->perPage(),
                'total' => $model->total(),
                'previous_page_url' => $model->previousPageUrl(),
                'next_page_url' => $model->nextPageUrl(),
            ];
        }
        return response()->json($response)->setStatusCode($statusCode);
    }

    /**
     * This is a custom generic error response helper
     * @param string $message
     * @param int|null $statusCode
     * @param string|\Exception|null $error
     * @return JsonResponse
     */
    public function jsonErrorResponse(
        string $message,
        ?int $statusCode = 500,
        string|\Exception|null $error = null,
    ): JsonResponse
    {
        $response = [
            'message' => $message,
            'status_code' => $statusCode,
        ];

        // Only sending error to response if debug mode is enabled
        if (config('app.debug')) {
            $response['error'] = $error;
        }

        return response()->json($response)->setStatusCode($statusCode);
    }
}
