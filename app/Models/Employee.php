<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'employee_id',
        'branch',
        'position_title',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'address',
        'email',
        'start_date',
        'supervisor',
        'employee_type',
        'employee_status',
        'role',
    ];

    /**
     * Get the user account associated with the employee.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'email', 'email');
    }
}
