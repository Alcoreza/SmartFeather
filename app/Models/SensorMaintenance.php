<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorMaintenance extends Model
{
    protected $table = 'sensormaintenance';
    protected $primaryKey = 'maintenanceid';
    public $timestamps = false;

    protected $fillable = [
        'startdate',
        'enddate',
        'status',
        'sensors_sensorid',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensors_sensorid', 'sensorid');
    }
}
