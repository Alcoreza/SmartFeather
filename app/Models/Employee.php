<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Employee extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'EmployeeId';
    public $timestamps = false;

    protected $fillable = [
        'FirstName',
        'MiddleName',
        'LastName',
        'Suffix',
        'Role',
        'PhoneNumber',
        'Birthday',
        'Gender',
        'Address',
        'Password',
    ];

    public function setPasswordAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        $info = password_get_info($value);

        $this->attributes['Password'] = $info['algo'] === null
            ? Hash::make($value)
            : $value;
    }
}
