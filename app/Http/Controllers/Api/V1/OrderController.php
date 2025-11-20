<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management endpoints"
 * )
 * @method middleware(string $string)
 */
class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     tags={"Orders"},
     *     summary="Get user orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Orders retrieved successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->input('per_page', 15);

        if ($user->isCustomer()) {
            // Customers see only their orders
            $orders = $this->orderService->getCustomerOrders($user->id, $perPage);
        } elseif ($user->isVendor()) {
            // Vendors see orders containing their products
            $orders = $this->orderService->getVendorOrders($user->id, $perPage);
        } else {
            // Admin sees all orders
            $orders = $this->orderService->getAllOrders($perPage);
        }

        return $this->jsonResponse(
            'Orders retrieved successfully',
            200,
            OrderResource::collection($orders->items()),
            $orders,
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders",
     *     tags={"Orders"},
     *     summary="Create a new order",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreOrderRequest")),
     *     @OA\Response(response=201, description="Order created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(
                $request->validated(),
                auth()->id()
            );

            return $this->jsonResponse(
                'Order created successfully',
                201,
                [new OrderResource($order)],
            );
        } catch (\Exception $e) {
            return $this->jsonErrorResponse(
                'Failed to create order',
                422,
                $e->getMessage(),
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}",
     *     tags={"Orders"},
     *     summary="Get a specific order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order retrieved successfully"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function show(Order $order): JsonResponse
    {
        $user = auth()->user();

        // Authorization check
        if ($user->isCustomer() && $order->user_id !== $user->id) {
            return $this->jsonErrorResponse('You are not authorized to view this order', 403);
        }

        if ($user->isVendor()) {
            // Check if vendor has products in this order
            $hasProducts = $order->items()->whereHas('product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->exists();

            if (!$hasProducts) {
                return $this->jsonErrorResponse('You are not authorized to view this order', 403);
            }
        }

        $order->load(['items.product', 'items.variant', 'customer']);

        return $this->jsonResponse(
            'Order retrieved successfully',
            200,
            [new OrderResource($order)]
        );
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/orders/{id}/status",
     *     tags={"Orders"},
     *     summary="Update order status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending","processing","shipped","delivered","cancelled"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Order status updated"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $user = auth()->user();

        // Only admin and vendors can update order status
        if ($user->isCustomer()) {
            return $this->jsonErrorResponse('You are not authorized to update order status', 403);
        }

        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        try {
            $order = $this->orderService->updateOrderStatus($order, $request->status);

            return $this->jsonResponse(
                'Order status updated successfully',
                200,
                [new OrderResource($order)]
            );
        } catch (\Exception $e) {
            return $this->jsonErrorResponse(
                'Failed to update order status',
                422,
                $e->getMessage()
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/cancel",
     *     tags={"Orders"},
     *     summary="Cancel an order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order cancelled successfully"),
     *     @OA\Response(response=422, description="Cannot cancel order")
     * )
     */
    public function cancel(Order $order): JsonResponse
    {
        $user = auth()->user();

        // Customers can only cancel their own orders
        if ($user->isCustomer() && $order->user_id !== $user->id) {
            return $this->jsonErrorResponse('You are not authorized to cancel this order', 403);
        }

        try {
            $order = $this->orderService->cancelOrder($order);

            return $this->jsonResponse(
                'Order cancelled successfully',
                200,
                [new OrderResource($order)]
            );
        } catch (\Exception $e) {
            return $this->jsonErrorResponse(
                'Failed to cancel order',
                422,
                $e->getMessage()
            );
        }
    }
}
