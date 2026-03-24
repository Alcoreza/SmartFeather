<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionStats extends Model
{
    // Table name is 'production_stats'
    protected $table = 'production_stats';
    
    public $timestamps = false;

    protected $fillable = [
        'house_id',
        'eggs_hatched',
        'mortality',
        'recorded_at',
    ];

    /**
     * Get the house these stats belong to
     */
    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }
}
