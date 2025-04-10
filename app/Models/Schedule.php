<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'work_days',
        'day_off',
        'holidays',
    ];

    protected $casts = [
        'work_days' => 'array',
        'holidays' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
