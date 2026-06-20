<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VitaminRefillRecord extends Model
{
    protected $table = 'vitamin_refill_records';

    protected $fillable = [
        'inventory_id',
        'house_id',
        'pen_id',
        'bottles_used',
        'recorded_at',
        'task_id',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function pen()
    {
        return $this->belongsTo(Pen::class, 'pen_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
