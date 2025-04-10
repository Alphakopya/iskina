<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fingerprint extends Model
{
    /** @use HasFactory<\Database\Factories\FingerprintFactory> */
    use HasFactory;

    protected $fillable = [
        'fingerprint_id',
        'employee_id',
        'name',
        'branch',
        'fingerprint_select',
        'del_fingerid',
        'add_fingerid',
        'mode',
    ];

    /**
     * Get the user account associated with the employee.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'employee_id', 'employee_id');
    }
}
