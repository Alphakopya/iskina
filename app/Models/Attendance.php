<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'fingerprint_id',
        'name',
        'branch',
        'date',
        'time_in',
        'break_in',
        'break_out',
        'time_out',
    ];

}
