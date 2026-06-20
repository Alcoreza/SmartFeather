<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorInspectionLog extends Model
{
    protected $table = 'sensor_inspection_logs';

    protected $fillable = [
        'task_id',
        'employee_id',
        'house_id',
        'pen_id',
        'sensor_present',
        'sensor_clean_unblocked',
        'no_visible_damage_or_loose_wiring',
        'power_status_on',
        'placement_secure',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sensor_present' => 'boolean',
        'sensor_clean_unblocked' => 'boolean',
        'no_visible_damage_or_loose_wiring' => 'boolean',
        'power_status_on' => 'boolean',
        'placement_secure' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'EmployeeId');
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function pen()
    {
        return $this->belongsTo(Pen::class, 'pen_id');
    }
}
