<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventories';

    protected $fillable = [
        'item_name',
        'type',
        'unit',
        'purchase_date',
        'initial_stock',
        'remaining_stock',
    ];
}
