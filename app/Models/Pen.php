<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pen extends Model
{
    protected $table = 'pen';

    public $timestamps = false;

    protected $fillable = [
        'house_id',
        'pen_name',
        'capacity',
        'population',
        'eggs_hatched',
        'mortality',
        'recorded_at',
        'archived_at',
        'current_batch_id',
        'batch_started_at',
        'feeder_count',
        'drinker_count',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'archived_at' => 'datetime',
        'batch_started_at' => 'datetime',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function currentBatch()
    {
        return $this->belongsTo(FlockBatch::class, 'current_batch_id');
    }

    public function runningBatch()
    {
        return $this->hasOne(FlockBatch::class, 'pen_id')
            ->where('status', 'Running');
    }

    public function weightSamplingLogs()
    {
        return $this->hasMany(WeightSamplingLog::class, 'pen_id');
    }

    public function latestWeightSamplingLog()
    {
        return $this->hasOne(WeightSamplingLog::class, 'pen_id')->latestOfMany('id');
    }

    public function cleaningLogs()
    {
        return $this->hasMany(CleaningLog::class, 'pen_id');
    }

    public function feedRefillRecords()
    {
        return $this->hasMany(FeedRefillRecord::class, 'pen_id');
    }

    public function vitaminRefillRecords()
    {
        return $this->hasMany(VitaminRefillRecord::class, 'pen_id');
    }

    public function sensorInspectionLogs()
    {
        return $this->hasMany(SensorInspectionLog::class, 'pen_id');
    }
}
