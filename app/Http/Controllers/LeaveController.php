<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Employee;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leaves = Leave::paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $leaves
        ]);
    }

    public function accept(Request $request, Leave $leave)
    {
        if ($leave->status !== 'Pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending leave requests can be accepted'
            ], 400);
        }

        $leave->update([
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => Auth::id() // Assuming you want to track who approved it
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave request approved successfully',
            'data' => $leave
        ]);
    }

    /**
     * Reject a leave request
     */
    public function reject(Request $request, Leave $leave)
    {
        if ($leave->status !== 'Pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending leave requests can be rejected'
            ], 400);
        }

        $leave->update([
            'status' => 'Rejected',
            'rejected_at' => now(),
            'rejected_by' => Auth::id() // Assuming you want to track who rejected it
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave request rejected successfully',
            'data' => $leave
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'employee_id' => 'required',
            'leave_type' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'reason_leave' => 'sometimes|required',
        ]);

        $leave = Leave::create($fields);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave Request created successfully',
            'data' => $leave
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Leave $leave)
    {
        $leaves = Leave::paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $leave
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Leave $leave)
    {
        $fields = $request->validate([
            'employee_id' => 'sometimes|required' . $leave->employee_id,
            'leave_type' => 'sometimes|required',
            'start_date' => 'sometimes|required',
            'end_date' => 'sometimes|required',
            'status' => 'sometimes|required',
        ]);

        $leave->update($fields);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave Request updated successfully',
            'data' => $leave
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Leave $leave)
    {
        $leave->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Leave Request deleted successfully'
        ]);
    }


    public function myLeaves(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
        $employee = Employee::where('email', $user->email)->first();
        $leaves = Leave::where('employee_id', $employee->employee_id)->paginate(10); // Fetch only their leaves

        return response()->json([
            'status' => 'success',
            'data' => $leaves
        ]);
    }
}
