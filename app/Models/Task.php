<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'taskid';
    public $timestamps = false;

    protected $fillable = [
        'tasktype',
        'detailedtask',
        'timeassigned',
        'finishby',
        'status',
        'notes',
        'time_completed',
        'user_employeeid',
        'house_houseid',
        'pennumber',
        'prioritylevel',
        'photourl',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_employeeid', 'EmployeeId');
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_houseid');
    }

    public function pen()
    {
        return $this->belongsTo(Pen::class, 'pennumber', 'id');
    }
}
