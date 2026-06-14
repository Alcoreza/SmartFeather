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
        'Username',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $employee) {
            if (empty($employee->Username)) {
                $employee->Username = self::buildUsername(
                    $employee->FirstName ?? '',
                    $employee->LastName ?? ''
                );
            }
        });
    }

    public static function buildUsername(string $firstName, string $lastName): string
    {
        $initial = mb_substr(trim($firstName), 0, 1, 'UTF-8');
        $surname = trim($lastName);

        return mb_strtoupper($initial . $surname, 'UTF-8');
    }

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
