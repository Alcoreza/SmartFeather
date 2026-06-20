<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    // Table name is 'house' (without 's')
    protected $table = 'house';
    
    public $timestamps = false;

    protected $fillable = [
        'house_number',
        'number_of_pens',
        'status',
        'start_date',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    /**
     * Get all pens for this house
     */
    public function pens()
    {
        return $this->hasMany(Pen::class, 'house_id');
    }
}
