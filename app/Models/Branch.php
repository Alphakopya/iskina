<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    /** @use HasFactory<\Database\Factories\BranchFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_name',
        'location',
        'contact_number',
        'branch_manager',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'branch', 'branch');
    }
}
