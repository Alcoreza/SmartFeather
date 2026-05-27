<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelEntryLog extends Model
{
    protected $table = 'personnel_entry_logs';

    protected $fillable = [
        'name',
        'role',
        'date',
        'time',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}