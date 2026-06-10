<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelBiosecurityLog extends Model
{
    protected $table = 'personnel_biosecurity_logs';

    protected $fillable = [
        'name',
        'role',
        'house',
        'date',
        'time',
        'foot_bath',
        'boots_changed',
        'protective_clothing',
        'employee_id',
        'personnel_entry_log_id',
        'house_id',
        'task_id',
        'pen_id',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
