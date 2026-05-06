<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pen extends Model
{
    protected $table = 'pen';

    public $timestamps = false;

    protected $fillable = [
        'house_id',
        'pen_name',
        'capacity',
        'population',
        'eggs_hatched',
        'mortality',
        'recorded_at',
        'current_batch_id',
        'batch_started_at',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function currentBatch()
    {
        return $this->belongsTo(FlockBatch::class, 'current_batch_id');
    }
}
