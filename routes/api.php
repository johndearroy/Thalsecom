<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return ["test" => \App\Models\User::query()->count()];
});


/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        // Protected auth routes
        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Product routes
    Route::prefix('products')->group(function () {
        // Public product routes
        Route::get('/', [ProductController::class, 'index']);
        Route::get('search', [ProductController::class, 'search']);
        Route::get('{product}', [ProductController::class, 'show']);

        // Protected product routes
        Route::middleware('auth:api')->group(function () {
            Route::post('/', [ProductController::class, 'store']);
            Route::put('{product}', [ProductController::class, 'update']);
            Route::delete('{product}', [ProductController::class, 'destroy']);
            Route::post('import', [ProductController::class, 'import']);
        });
    });

    // Order routes (all protected)
    Route::middleware('auth:api')->prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('{order}', [OrderController::class, 'show']);
        Route::patch('{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('{order}/cancel', [OrderController::class, 'cancel']);
    });

    // Inventory routes (protected)
    Route::middleware('auth:api')->prefix('inventory')->group(function () {
        Route::post('variants/{variant}/adjust', [InventoryController::class, 'adjustStock']);
        Route::post('variants/{variant}/add', [InventoryController::class, 'addStock']);
        Route::get('variants/{variant}/logs', [InventoryController::class, 'getLogs']);
        Route::get('alerts', [InventoryController::class, 'getLowStockAlerts']);
        Route::post('alerts/{alert}/resolve', [InventoryController::class, 'resolveAlert']);
        Route::get('stock-summary', [InventoryController::class, 'getStockSummary']);
    });
});
