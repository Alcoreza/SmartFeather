<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorConfiguration extends Model
{
    protected $table = 'sensorconfigurations';
    protected $primaryKey = 'configid';
    public $timestamps = false;

    protected $fillable = [
        'lowestthreshold',
        'highestthreshold',
        'sensors_sensorid',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensors_sensorid', 'sensorid');
    }
}
