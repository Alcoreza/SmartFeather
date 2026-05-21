<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $table = 'sensor_readings';
    protected $primaryKey = 'reading_id';
    public $timestamps = false;

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