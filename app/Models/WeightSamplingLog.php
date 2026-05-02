<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightSamplingLog extends Model
{
    protected $table = 'weight_sampling_logs';

    protected $fillable = [
        'date',
        'time',
        'house',
        'pen',
        'batch',
        'flocks_with_cases',
        'age',
        'average_weight',
        'target',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}