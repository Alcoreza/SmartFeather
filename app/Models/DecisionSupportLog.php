<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionSupportLog extends Model
{
    protected $table = 'decision_support_logs';

    protected $fillable = [
        'house_id',
        'recommendation_text',
        'data_snapshot',
        'status',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'data_snapshot' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the house associated with this decision support log
     */
    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    /**
     * Scope to get active recommendations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get recommendations for a specific house
     */
    public function scopeForHouse($query, $houseId)
    {
        return $query->where('house_id', $houseId);
    }
}
