<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CleaningLog extends Model
{
    protected $table = 'cleaning_logs';

    protected $fillable = [
        'house',
        'pen',
        'activity',
        'disinfectant_used',
        'performed_by',
        'date',
        'time',
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
}