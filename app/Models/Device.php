<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'device_mode',
        'branch',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch', 'branch_id');
    }
}
