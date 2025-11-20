<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LowStockAlert;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Inventory",
 *     description="Inventory management endpoints"
 * )
 * @method middleware(string $string)
 */
class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/variants/{id}/logs",
     *     tags={"Inventory"},
     *     summary="Get inventory logs for a variant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Inventory logs retrieved")
     * )
     */
    public function getLogs(ProductVariant $variant, Request $request): JsonResponse
    {
        $user = auth()->user();

        // Vendors can only see logs for their own products
        if ($user->isVendor() && $variant->product->user_id !== $user->id) {
            return $this->jsonErrorResponse('You are not authorized to view these inventory logs', 403);
        }

        $logs = $this->inventoryService->getInventoryLogs(
            $variant,
            $request->input('per_page', 15)
        );

        return $this->jsonResponse(
            'Inventory logs retrieved',
            200,
            $logs->items(),
            $logs
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/variants/{id}/add",
     *     tags={"Inventory"},
     *     summary="Add stock to variant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=50),
     *             @OA\Property(property="reason", type="string", example="Restocked from supplier")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Stock added successfully")
     * )
     */
    public function addStock(Request $request, ProductVariant $variant): JsonResponse
    {
        $user = auth()->user();

        // Vendors can only manage their own products
        if ($user->isVendor() && $variant->product->user_id !== $user->id) {
            return $this->jsonErrorResponse('You are not authorized to modify this inventory', 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $log = $this->inventoryService->addStock(
                $variant,
                $validated['quantity'],
                $validated['reason'] ?? 'Stock added manually'
            );

            $data = [
                'variant_id' => $variant->id,
                'previous_stock' => $log->previous_stock,
                'new_stock' => $log->new_stock,
                'quantity_added' => $validated['quantity'],
            ];

            return $this->jsonResponse('Stock added successfully', 200, $data);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse('Failed to add stock', 422, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/variants/{id}/adjust",
     *     tags={"Inventory"},
     *     summary="Adjust stock quantity",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_quantity"},
     *             @OA\Property(property="new_quantity", type="integer", example=100),
     *             @OA\Property(property="reason", type="string", example="Physical inventory count")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Stock adjusted successfully")
     * )
     */
    public function adjustStock(Request $request, ProductVariant $variant): JsonResponse
    {
        $user = auth()->user();

        // Vendors can only manage their own products
        if ($user->isVendor() && $variant->product->user_id !== $user->id) {
            return $this->jsonErrorResponse('You are not authorized to modify this inventory', 403);
        }

        $validated = $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $log = $this->inventoryService->adjustStock(
                $variant,
                $validated['new_quantity'],
                $validated['reason'] ?? 'Stock adjusted manually'
            );

            $data = [
                'variant_id' => $variant->id,
                'previous_stock' => $log->previous_stock,
                'new_stock' => $log->new_stock,
                'difference' => $log->quantity,
            ];

            return $this->jsonResponse('Stock adjusted successfully', 200, $data);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse('Failed to adjust stock', 422, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/alerts",
     *     tags={"Inventory"},
     *     summary="Get all low stock alerts",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Low stock alerts retrieved")
     * )
     */
    public function getLowStockAlerts(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = LowStockAlert::with(['variant.product'])
            ->where('is_resolved', false);

        // Vendors see only their products
        if ($user->isVendor()) {
            $query->whereHas('variant.product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $alerts = $query->latest()
            ->paginate($request->input('per_page', 15));

        return $this->jsonResponse(
            'Low stock alerts retrieved',
            200,
            $alerts->items(),
            $alerts
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/alerts/{id}/resolve",
     *     tags={"Inventory"},
     *     summary="Mark low stock alert as resolved",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Alert resolved")
     * )
     */
    public function resolveAlert(LowStockAlert $alert): JsonResponse
    {
        $user = auth()->user();

        // Check authorization
        if ($user->isVendor() && $alert->variant->product->user_id !== $user->id) {
            return $this->jsonErrorResponse('You are not authorized to resolve this alert', 403);
        }

        $alert->update(['is_resolved' => true]);

        return $this->jsonResponse('Alert resolved successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/stock-summary",
     *     tags={"Inventory"},
     *     summary="Get inventory stock summary",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Stock summary retrieved")
     * )
     */
    public function getStockSummary(): JsonResponse
    {
        $user = auth()->user();

        $query = ProductVariant::query();

        // Filter by vendor
        if ($user->isVendor()) {
            $query->whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $totalVariants = $query->count();
        $outOfStock = $query->where('stock_quantity', 0)->count();
        $lowStock = $query->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', 10)
            ->count();
        $inStock = $query->where('stock_quantity', '>', 10)->count();
        $totalStockValue = $query->sum('stock_quantity');

        $data = [
            'total_variants' => $totalVariants,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'total_stock_units' => $totalStockValue,
        ];

        return $this->jsonResponse('Stock summary retrieved', 200, $data);
    }
}
