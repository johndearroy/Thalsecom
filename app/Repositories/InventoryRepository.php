<?php

namespace App\Repositories;

use App\Models\InventoryLog;

class InventoryRepository extends BaseRepository
{
    public function __construct(InventoryLog $model)
    {
        parent::__construct($model);
    }
}
