<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlockBatch extends Model
{
    protected $table = 'flock_batches';

    protected $fillable = [
        'batch_code',
        'house_id',
        'pen_id',
        'started_at',
        'status',
        'initial_population',
        'ended_at',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function pen()
    {
        return $this->belongsTo(Pen::class, 'pen_id');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'Running');
    }

    public function scopeByHouse($query, $houseId)
    {
        return $query->where('house_id', $houseId);
    }
}
