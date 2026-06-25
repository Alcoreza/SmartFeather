<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    protected $fillable = [
        'sensorid',
        'value',
        'recorded_at',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensorid', 'sensorid');
    }
}