<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopulationRecord extends Model
{
    protected $table = 'population_record';

    public $timestamps = false;

    protected $fillable = [
        'pen_id',
        'eggs_hatched',
        'mortality',
        'recorded_at',
        'running_population',
        'task_id',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'eggs_hatched' => 'integer',
        'mortality' => 'integer',
        'running_population' => 'integer',
    ];

    public function pen()
    {
        return $this->belongsTo(Pen::class, 'pen_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'taskid');
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }
}
