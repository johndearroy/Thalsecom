<?php

return [
    'order_number_prefix' => env('ORDER_NUMBER_PREFIX', 'ORD-'),
    'low_stock_threshold' => env('LOW_STOCK_THRESHOLD', 10),
    'low_stock_schedule_at' => env('LOW_STOCK_SCHEDULE_AT', '08:00'),
];
