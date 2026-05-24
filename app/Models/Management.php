<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Management extends Model
{
    protected $table = 'task_type';
    public $timestamps = false;

    protected $fillable = [
        'task',
    ];
}
