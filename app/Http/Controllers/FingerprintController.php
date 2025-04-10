<?php

namespace App\Http\Controllers;

use App\Models\Fingerprint;
use App\Models\Attendance;
use App\Models\Device;
use Illuminate\Http\Request;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FingerprintController extends Controller
{
    public function register(Request $request)
    {
        $request->validate(['employee_id' => 'required|exists:employees,employee_id']);
        $fingerprint = Fingerprint::where('employee_id', $request->employee_id)->first();

        if (!$fingerprint || !$fingerprint->fingerprint_id) {
            return response()->json(['message' => 'Fingerprint record not initialized or no ID assigned'], 400);
        }

        // Already in enroll mode from creation, just confirm
        return response()->json(['message' => 'Fingerprint registration initiated', 'fingerprint_id' => $fingerprint->fingerprint_id]);
    }

    public function getEmployee($employeeId)
    {
        $employee = Employee::where('employee_id', $employeeId);
        $fingerprint = Fingerprint::where('employee_id', $employeeId)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json([
            'data' => $employee,
            'fingerprint' => $fingerprint,
        ]);
    }
    
    public function handleFingerprint(Request $request)
    {
        // Set the timezone to Asia/Manila for this method
        Carbon::setLocale('en');
        date_default_timezone_set('Asia/Manila');

        $fingerprint = Fingerprint::first();
        $device = Device::first();
        if (!$fingerprint || !$device) {
            return response('Device or fingerprint not found', 404);
        }
        $mode = $device ? $device->device_mode : 'attendance';
        
        if ($request->has('enroll_status')) {
            $status = $request->input('enroll_status');
            $fingerID = $request->input('fingerprint_id');
            $message = $request->input('message');
            \Log::info("Enrollment Status Received: fingerID={$fingerID}, status={$status}, message={$message}");
            
            $fingerprint = Fingerprint::where('fingerprint_id', $fingerID)->first();
            if ($fingerprint) {
                if ($status === 'success') {
                    $fingerprint->update([
                        'add_fingerid' => 1,
                        'fingerprint_select' => 0,
                    ]);
                    \Cache::put("enroll_status_{$fingerID}", [
                        'status' => 'success',
                        'message' => 'Fingerprint successfully enrolled',
                    ], now()->addMinutes(10)); // Extend to 10 minutes for success
                    return response()->json([
                        'status' => 'success',
                        'message' => $message,
                        'employee_id' => $fingerprint->employee_id,
                        'fingerprint_id' => $fingerID,
                    ], 200);
                }
                \Cache::put("enroll_status_{$fingerID}", [
                    'status' => $status,
                    'message' => $message,
                ], now()->addMinutes(5)); // 5 minutes for other statuses
                return response()->json(['status' => $status, 'message' => $message]);
            }
            return response()->json(['message' => 'Fingerprint not found'], 404);
        }

        if ($mode === 'attendance' && $request->has('FingerID')) {
            $fingerID = $request->input('FingerID');
            $fingerprint = Fingerprint::where('fingerprint_id', $fingerID)->first();

            if (!$fingerprint) {
                return response('invalid', 200);
            }

            $today = Carbon::today();

            $record = Attendance::where('employee_id', $fingerprint->employee_id)
                ->whereDate('date', $today)
                ->whereNull('time_out')
                ->first();
            Log::info("Attendance Check: fingerID={$fingerID}, employee_id={$fingerprint->employee_id}, date={$today}");
            if ($record) {
                $record->update([
                    'time_out' => Carbon::now(),
                ]);
                return response("logout{$fingerprint->name}", 200);
            } else {
                Attendance::create([
                    'employee_id' => $fingerprint->employee_id,
                    'fingerprint_id' => $fingerprint->fingerprint_id,
                    'name' => $fingerprint->name,
                    'branch' => $fingerprint->branch,
                    'date' => $today,
                    'time_in' => Carbon::now(),
                ]);
                return response("login{$fingerprint->name}", 200);
            }
        }

        if ($mode === 'break' && $request->has('FingerID')) {
            $fingerID = $request->input('FingerID');
            $fingerprint = Fingerprint::where('fingerprint_id', $fingerID)->first();

            $today = Carbon::today();

            $record = Attendance::where('employee_id', $fingerprint->employee_id)
                ->whereDate('date', $today)
                ->first();
            Log::info("Attendance Check: fingerID={$fingerID}, employee_id={$fingerprint->employee_id}, date={$today}");
            if ($record) {
                if (!$record->break_in) {
                    $record->update([
                        'break_in' => Carbon::now(),
                    ]);
                    Log::info("Break In: employee_id={$fingerprint->employee_id}, break_in=" . Carbon::now());
                    return response("login{$fingerprint->name}", 200);
                } else {
                    $record->update([
                        'break_out' => Carbon::now(),
                    ]);
                    return response("logout{$fingerprint->name}", 200);
                }
            }
            Log::info("Attendance Check: employee_id={$fingerprint->employee_id}, date={$today} - No record found");
            return response()->json(['message' => 'Fingerprint not found'], 404);
        }
        
        if ($request->input('Get_Fingerid') === 'get_id') {
            $fingerprint = Fingerprint::where('fingerprint_select', 1)->first();
            if ($fingerprint) {
                return response("add-id{$fingerprint->fingerprint_id}", 200);
            }
            return response('no-enroll-id', 200);
        }

        return response("mode:{$mode}", 200);
    }

    public function getEnrollmentStatus(Request $request)
    {
        // Set the timezone to Asia/Manila for this method
        Carbon::setLocale('en');
        date_default_timezone_set('Asia/Manila');

        $fingerID = $request->query('fingerprint_id');
        $status = \Cache::get("enroll_status_{$fingerID}");
        \Log::info("Cache Retrieval: fingerID={$fingerID}, status=" . json_encode($status));
        if ($status) {
            return response()->json($status);
        }
        return response()->json(['status' => 'pending', 'message' => 'Waiting for enrollment status']);
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $branch = $request->query('branch');
        $employeeId = $request->query('employee_id');

        $fingerprints = Fingerprint::where('add_fingerid', 1)
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%");
            })->when($branch, function ($query, $branch) {
                return $query->where('branch', $branch);
            })->when($employeeId, function ($query, $employeeId) {
                return $query->where('employee_id', $employeeId);
            })->with('employee')->paginate(10);

        if ($request->expectsJson()) {
            return response()->json(['data' => $fingerprints]);
        }

        return view('fingerprint-list', compact('fingerprints'));
    }

    public function fingerprintIndex(Request $request)
    {
        $search = $request->query('search');
        $branch = $request->query('branch');
        $employeeId = $request->query('employee_id');

        $fingerprints = Fingerprint::where('add_fingerid', 0)
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%");
            })->when($branch, function ($query, $branch) {
                return $query->where('branch', $branch);
            })->when($employeeId, function ($query, $employeeId) {
                return $query->where('employee_id', $employeeId);
            })->with('employee')->paginate(10);

        if ($request->expectsJson()) {
            return response()->json(['data' => $fingerprints]);
        }

        return view('fingerprint-list', compact('fingerprints'));
    }

    public function select(Request $request)
    {
        $request->validate(['employee_id' => 'required|exists:employees,employee_id']);
        $fingerprint = Fingerprint::where('employee_id', $request->employee_id)->first();

        if (!$fingerprint) {
            return response()->json(['message' => 'Fingerprint record not found'], 404);
        }

        $fingerprint->update(['fingerprint_select' => 1]);
        return response()->json(['message' => 'Fingerprint selected']);
    }

    public function unselect(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $fingerprint = Fingerprint::where('employee_id', $employeeId)->first();

        if ($fingerprint) {
            $fingerprint->update(['fingerprint_select' => 0]);
            \Log::info("Fingerprint unselected: employee_id={$employeeId}, fingerprint_select set to 0");
            return response()->json(['message' => 'Employee unselected successfully'], 200);
        }

        return response()->json(['message' => 'Fingerprint not found'], 404);
    }

    public function selectForEdit(Request $request)
    {
        $fingerprintId = $request->input('fingerprint_id');
        $fingerprint = Fingerprint::where('fingerprint_id', $fingerprintId)->first();

        if ($fingerprint) {
            $fingerprint->update(['fingerprint_select' => 1]);
            \Log::info("Fingerprint selected for edit: fingerprint_id={$fingerprintId}");
            return response()->json(['message' => 'Fingerprint selected for edit'], 200);
        }

        return response()->json(['message' => 'Fingerprint not found'], 404);
    }

    public function deleteFingerprint(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $fingerprint = Fingerprint::where('employee_id', $employeeId)->first();

        if ($fingerprint) {
            $fingerprint->update([
                'add_fingerid' => 0,
                'fingerprint_select' => 1,
            ]);
            \Cache::forget("enroll_status_{$fingerprint->fingerprint_id}"); // Clear status cache
            return response()->json(['message' => 'Fingerprint deleted, ready for re-enrollment'], 200);
        }

        return response()->json(['message' => 'Fingerprint not found'], 404);
    }
}