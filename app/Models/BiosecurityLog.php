<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiosecurityLog extends Model
{
    protected $table = 'biosecurity_logs';

    protected $fillable = [
        'type',
        'house',
        'pen',
        'activity',
        'disinfectant_used',
        'performed_by',
        'name',
        'role',
        'date',
        'time',
        'foot_bath',
        'boots_changed',
        'protective_clothing',
        'time_in',
        'time_out',
        'purpose',
        'sanitation',
        'ppe',
        'monitored_by',
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