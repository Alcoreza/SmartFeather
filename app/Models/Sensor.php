<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    protected $table = 'sensors';
    protected $primaryKey = 'sensorid';
    public $timestamps = false;

    protected $fillable = [
        'sensorname',
        'sensortype',
        'house_houseid',
        'pen_penid',
        'status',
        'feeder_number',
        'drinker_number',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_houseid', 'id');
    }

    public function pen()
    {
        return $this->belongsTo(Pen::class, 'pen_penid', 'id');
    }

    public function configuration()
    {
        return $this->hasOne(SensorConfiguration::class, 'sensors_sensorid', 'sensorid');
    }

    public function maintenances()
    {
        return $this->hasMany(SensorMaintenance::class, 'sensors_sensorid', 'sensorid');
    }

    public function readings()
    {
        return $this->hasMany(SensorReading::class, 'sensorid', 'sensorid');
    }

    public function latestReading()
    {
        return $this->hasOne(SensorReading::class, 'sensorid', 'sensorid')
                    ->latest('recorded_at');
    }
}