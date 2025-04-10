<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource with pagination.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'employee_id' => 'required|unique:employees,employee_id',
            'branch' => 'required',
            'position_title' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'date_of_birth' => 'required|date',
            'gender' => 'required',
            'address' => 'required',
            'email' => 'required|email|unique:employees,email|unique:users,email',
            'start_date' => 'required|date',
            'supervisor' => 'required',
            'employee_type' => 'required',
            'employee_status' => 'required',
            'role' => 'required',
        ]);

        $employee = Employee::create($fields);

        $user = User::create([
            'name' => $fields['first_name'] . ' ' . $fields['last_name'],
            'email' => $fields['email'],
            'password' => Hash::make('ISKINA-' . strtoupper($fields['last_name'])),
            'role' => $fields['role'],
        ]);

        $usedIds = Fingerprint::whereNotNull('fingerprint_id')->pluck('fingerprint_id')->toArray();
        $availableIds = array_diff(range(1, 127), $usedIds);
        if (empty($availableIds)) {
            return response()->json(['message' => 'No available fingerprint IDs'], 400);
        }
        $fingerprintId = $availableIds[array_rand($availableIds)];

        $fingerprint = Fingerprint::create([
            'employee_id' => $employee->employee_id,
            'name' => $employee->first_name . ' ' . $employee->last_name,
            'branch' => $employee->branch,
            'fingerprint_id' => $fingerprintId,
            'fingerprint_select' => 0, // Not selected yet
            'add_fingerid' => 0, // Not added yet
            'del_fingerid' => 0, // Not deleted
            'mode' => 'enroll',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee, User account, and Fingerprint record created successfully',
            'data' => [
                'employee' => $employee,
                'user' => $user,
                'fingerprint' => $fingerprint,
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Employee retrieved successfully',
            'data' => $employee
        ]);
    }

    public function getEmployeesByBranch(Request $request)
    {
        $branchId = $request->query('branch'); // Get branch_id from query parameter
        $search = $request->query('search');   // Get search query for employee_id

        if (!$branchId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Branch parameter is required'
            ], 400);
        }



        // Find the branch by branch_id to get the branch_name
        $branch = Branch::where('branch_name', $branchId)->first();

        if (!$branch) {
            return response()->json([
                'status' => 'error',
                'message' => 'Branch not found',
                'data' => []
            ], 404);
        }

        // Fetch employees by branch_name with optional search filter
        $query = Employee::where('branch', $branch->branch_name);

        if ($search) {
            $query->where('employee_id', 'like', "%{$search}%");
        }

        $employees = $query->get();

        if ($employees->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No employees found for this branch',
                'data' => []
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $employees
        ], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        
        $fields = $request->validate([
            'employee_id' => 'sometimes|required|unique:employees,employee_id,' . $employee->id,
            'branch' => 'sometimes|required',
            'position_title' => 'sometimes|required',
            'first_name' => 'sometimes|required',
            'last_name' => 'sometimes|required',
            'date_of_birth' => 'sometimes|required|date',
            'gender' => 'sometimes|required',
            'address' => 'sometimes|required',
            'email' => 'sometimes|required|email|unique:employees,email,' . $employee->id,
            'start_date' => 'sometimes|required|date',
            'supervisor' => 'sometimes|required',
            'employee_type' => 'sometimes|required',
            'employee_status' => 'sometimes|required',
            'role' => 'sometimes|required',
        ]);

        $employee->update($fields);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee updated successfully',
            'data' => $employee
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        Gate::authorize('modify', $employee);

        $employee->update(['employee_status' => 'archived']);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee archived successfully'
        ]);
    }

    public function getEmployeeInfo()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized. Please log in.'], 401);
        }

        $employee = Employee::where('email', $user->email)->first();

        if ($employee) {
            return response()->json(['employee' => $employee], 200);
        }

        if ($user->role === 'admin') {
            return response()->json(['role' => 'admin', 'message' => 'Admin user. No employee record.'], 200);
        }

        return response()->json(['message' => 'User not found or unauthorized.'], 404);
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $employees = Employee::when($search, function ($query, $search) {
            return $query->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('employee_id', 'like', "%{$search}%");
        })->paginate(10);

        return response()->json(['data' => $employees]);
    }

    public function byBranch(Request $request)
    {
        $branch = $request->query('branch');
        $search = $request->query('search');
        $employees = Employee::where('branch', $branch)
            ->when($search, function ($query, $search) {
                return $query->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('employee_id', 'like', "%{$search}%");
            })->get();

        return response()->json(['data' => $employees]);
    }
}
