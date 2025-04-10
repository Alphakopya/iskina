<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('email', $user->email)->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'first_name' => $employee->first_name ?? null,
                'last_name' => $employee->last_name ?? null,
                'date_of_birth' => $employee->date_of_birth ?? null,
                'gender' => $employee->gender ?? null,
                'address' => $employee->address ?? null,
            ]
        ]);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('email', $user->email)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee record not found'
            ], 404);
        }

        // Validate the request
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'current_password' => 'required|string',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 403);
        }

        // Update Employee model
        $employee->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'date_of_birth' => $employee->date_of_birth,
                'gender' => $employee->gender,
                'address' => $employee->address,
            ]
        ]);
    }
}