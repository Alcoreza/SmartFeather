<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user'; // your table name

    protected $primaryKey = 'EmployeeId';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'FirstName',
        'MiddleName',
        'LastName',
        'Suffix',
        'Role',
        'PhoneNumber',
        'Address',
        'Birthday',
        'Gender',
        'Password',
        'Username',
        'is_active',
    ];

    protected $hidden = [
        'Password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'Birthday' => 'date',
            'Password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
    public $timestamps = false;
}
