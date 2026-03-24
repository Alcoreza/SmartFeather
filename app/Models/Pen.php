<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pen extends Model
{
    // Table name is 'pen'
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
    ];

    /**
     * Get the house this pen belongs to
     */
    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }
}
