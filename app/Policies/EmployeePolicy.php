<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'hr']);
    }

    /**
     * Determine whether the user can view a specific employee.
     */
    public function view(User $user, Employee $employee)
    {
        return in_array($user->role, ['admin', 'hr']) || $user->id === $employee->user_id;
    }

    /**
     * Determine whether the user can create an employee.
     */
    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'hr']);
    }

    /**
     * Determine whether the user can update an employee.
     */
    public function update(User $user, Employee $employee)
    {
        return in_array($user->role, ['admin', 'hr']);
    }

    /**
     * Determine whether the user can delete an employee.
     */
    public function archive(User $user, Employee $employee)
    {
        return in_array($user->role, ['admin', 'hr']);
    }
}
