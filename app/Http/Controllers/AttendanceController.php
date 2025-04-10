<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date');
        $branch = $request->query('branch');
        $page = $request->query('page', 1);
        $perPage = 10;

        $query = Attendance::select('attendances.*')
        ->when($date, function ($query, $date) {
            return $query->whereDate('time_in', $date);
        })
        ->when($branch, function ($query, $branch) {
            return $query->where('attendances.branch', $branch);
        })
        ->when($request->query('search'), function ($query, $search) {
            return $query->whereRaw("attendances.name LIKE ?", ["%$search%"])
                ->orWhere('attendances.employee_id', 'LIKE', "%$search%");
        });

        $total = $query->count();
        $records = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Add employee data to each record
        $records = $records->map(function ($record) {
            return [
                'id' => $record->id,
                'employee_id' => $record->employee_id,
                'name' => $record->name,
                'branch' => $record->branch,
                'time_in' => $record->time_in,
                'break_in' => $record->break_in,
                'break_out' => $record->break_out,
                'time_out' => $record->time_out,
                'status' => $record->status,
            ];
        });

        // Mimic pagination structure
        $lastPage = ceil($total / $perPage);
        $paginatedData = [
            'data' => $records,
            'current_page' => (int) $page,
            'last_page' => $lastPage,
            'total' => $total,
            'per_page' => $perPage,
        ];

        return response()->json(['data' => $paginatedData]);
    }
    public function getDeviceMode()
    {
        $device = Device::first();
        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        return response()->json(['mode' => $device->device_mode]);
    }

    public function fetchMyAttendance(Request $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $employee = \App\Models\Employee::where('email', $user->email)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        // Fetch today's attendance
        $todayAttendance = \App\Models\Attendance::where('employee_id', $employee->employee_id)
            ->whereDate('time_in', $today)
            ->get()
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'employee_id' => $attendance->employee_id,
                    'time_in' => Carbon::parse($attendance->time_in)->format('h:i A'),
                    'time_out' => $attendance->time_out ? Carbon::parse($attendance->time_out)->format('h:i A') : null,
                    'break_in' => $attendance->break_in ? Carbon::parse($attendance->break_in)->format('h:i A') : null,
                    'break_out' => $attendance->break_out ? Carbon::parse($attendance->break_out)->format('h:i A') : null,
                    'date' => $attendance->date,
                    // Add other fields as needed
                ];
            });

        // Paginate attendance history
        $historyAttendance = \App\Models\Attendance::where('employee_id', $employee->employee_id)
            ->whereDate('date', '<', $today)
            ->orderBy('time_in', 'desc')
            ->paginate(10)
            ->through(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'employee_id' => $attendance->employee_id,
                    'time_in' => Carbon::parse($attendance->time_in)->format('h:i A'),
                    'time_out' => $attendance->time_out ? Carbon::parse($attendance->time_out)->format('h:i A') : null,
                    'break_in' => $attendance->break_in ? Carbon::parse($attendance->break_in)->format('h:i A') : null,
                    'break_out' => $attendance->break_out ? Carbon::parse($attendance->break_out)->format('h:i A') : null,
                    'date' => $attendance->date,
                    // Add other fields as needed
                ];
            });

        return response()->json([
            'todayAttendance' => $todayAttendance,
            'historyAttendance' => $historyAttendance->items(),
            'historyPagination' => [
                'current_page' => $historyAttendance->currentPage(),
                'last_page' => $historyAttendance->lastPage(),
                'next_page_url' => $historyAttendance->nextPageUrl(),
                'prev_page_url' => $historyAttendance->previousPageUrl(),
            ],
        ]);
    }

    public function updateDeviceMode(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:attendance,break,enroll'
        ]);

        $device = \App\Models\Device::first();
        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $device->device_mode = $request->mode;
        $device->save();

        return response()->json(['message' => 'Device mode updated successfully', 'mode' => $device->device_mode]);
    }

}