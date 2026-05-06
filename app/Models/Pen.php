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
        'current_batch_code',
        'batch_started_at',
    ];

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }
}
