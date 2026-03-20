<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'user'; // Use the exact table name in Supabase
    protected $primaryKey = 'EmployeeId'; // exact PK
    public $timestamps = false; // if your table has no created_at/updated_at

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
        'Password', // optional
    ];
}
