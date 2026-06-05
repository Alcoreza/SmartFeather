<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightSamplingLog extends Model
{
    protected $table = 'weight_sampling_logs';

    protected $fillable = [
        'date',
        'time',
        'house',
        'pen',
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

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function pen()
    {
        return $this->belongsTo(Pen::class, 'pen_id');
    }

    public function batch()
    {
        return $this->belongsTo(FlockBatch::class, 'batch_id');
    }
}cleaningLogs = await loadBiosecurityRecords();

    return cleaningLogs.filter((record) => {
        const activity = String(record.activity || '').trim();
        return activity === 'Pen Disinfection';
    }).map((record) => ({
        ...record,
        house: record.house?.name || record.house || '--',
        pen: record.pen?.pen_name || record.pen || '--',
        performed_by: record.performed_by || '--',
        date: formatDate(record.date),
        time: formatTime(record.time),
    })farm-activity/cleaning-logs');
    return result.records || []farm-activity/sensor-inspection-logs');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        sensor_type: record.sensor?.sensor_type || 'Unknown',
        name: record.sensor?.name || '--',
        house_number: formatHouseNumber(record.house?.name || record.house_number),
        start_date: formatDate(record.recorded_at),
        end_date: formatDate(record.recorded_at),
        status: record.sensor_present ? 'Present' : 'Missing'refill-records');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        vitamin: record.inventory?.name || record.vitamin || '--',
        house_number: formatHouseNumber(record.house?.name || record.house_number),
        pen_name: record.pen?.pen_name || record.pen_name || '--',
        bottles_used: formatNumber(record.bottles_usedfill-records');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        feed: record.inventory?.name || record.feed || '--',
        house_number: formatHouseNumber(record.house?.name || record.house_number),
        pen_name: record.pen?.pen_name || record.pen_name || '--',
        feeder_number: record.feeder_number ?? '--',
        kilograms_used: formatNumber(record.kilograms_usedsampling-logs');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house: record.house?.name || record.house || '--',
        pen: record.pen?.pen_name || record.pen || '--',
        batch: record.batch?.batch_code || record.batch || '--',
        average_weight: record.average_weight ?? '--',
        target: record.target ?? '--',
        status: record.status ?? '--'pens');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house?.name || record.house_number),
        pen_name: record.pen_name || '--'