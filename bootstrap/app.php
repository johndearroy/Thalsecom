<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\RoleMiddleware;
use App\Jobs\CheckLowStockJob;
use App\Models\InventoryLog;
use App\Models\LowStockAlert;
use App\Models\ProductVariant;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        $middleware->api(prepend: [
            HandleCors::class,
        ]);

        // Apply ForceJsonResponse middleware to all API routes
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Check for low stock alerts daily
        $time = config('thalsecom.low_stock_schedule_at', '08:00');
        $schedule->call(function () {
            $threshold = config('thalsecom.low_stock_threshold', 10);
            $variants = ProductVariant::query()
                ->lowStock($threshold) // lowStock is a scope of ProductVariant
                ->get();

            foreach ($variants as $variant) {
                CheckLowStockJob::dispatch($variant->id);
            }
        })->daily()->at($time);

        // Clean up old low stock alerts (resolved ones older than 30 days)
        $schedule->call(function () {
            LowStockAlert::query()
                ->where('is_resolved', true)
                ->where('updated_at', '<', now()->subDays(30))
                ->delete();
        })->weekly();

        // Clean up old inventory logs (older than 1 year)
        $schedule->call(function () {
            InventoryLog::query()
                ->where('created_at', '<', now()->subYear())
                ->delete();
        })->monthly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * Handling not found exception's
         */
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            // Returning 404 not found json response for api(s)
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });
    })->create();
