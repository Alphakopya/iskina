<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request; // Use generic Request
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee; // Assuming you have an Employee model
class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $schedules = Schedule::with('employee')->paginate(10); // 10 items per page
        return response()->json([
            'data' => $schedules,
            'message' => 'Schedules retrieved successfully'
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Schedule store request received: ' . json_encode($request->all()));

            // Validate the request
            $validated = $request->validate([
                'employees' => 'required|array|min:1',
                'employees.*' => 'exists:employees,employee_id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'work_days' => 'required|array|min:1',
                'work_days.*' => 'date|distinct',
                'day_off' => 'required|date',
                'holidays' => 'nullable|array',
                'holidays.*' => 'date|distinct',
            ]);

            // Create date range for the new schedule
            $startDate = new \DateTime($validated['start_date']);
            $endDate = new \DateTime($validated['end_date']);
            $dateRange = [];
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $dateRange[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }

            // Filter work_days, day_off, and holidays to ensure they fall within the date range
            $workDays = array_filter($validated['work_days'], fn($date) => in_array($date, $dateRange));
            $dayOff = in_array($validated['day_off'], $dateRange) ? $validated['day_off'] : null;
            $holidays = array_filter($validated['holidays'] ?? [], fn($date) => in_array($date, $dateRange));

            // Check for duplicate dates across work_days, day_off, and holidays
            $allAssignedDates = array_merge($workDays, [$dayOff], $holidays);
            if (count($allAssignedDates) !== count(array_unique($allAssignedDates))) {
                return response()->json(['error' => 'Dates must be unique across work_days, day_off, and holidays'], 422);
            }

            // Fetch employee names for the provided employee IDs
            $employeeNames = Employee::whereIn('employee_id', $validated['employees'])
                ->pluck('first_name', 'employee_id')
                ->map(function ($firstName, $employeeId) {
                    $lastName = Employee::where('employee_id', $employeeId)->value('last_name');
                    return "$firstName $lastName";
                })
                ->toArray();

            // Check for schedule conflicts for each employee
            $conflictingEmployees = [];
            foreach ($validated['employees'] as $employeeId) {
                Log::info('Checking for schedule conflicts', [
                    'employee_id' => $employeeId,
                    'new_start_date' => $validated['start_date'],
                    'new_end_date' => $validated['end_date'],
                ]);

                // Find existing schedules for the employee
                $existingSchedules = Schedule::where('employee_id', $employeeId)
                    ->where(function ($query) use ($validated) {
                        $query->where('start_date', '<=', $validated['end_date'])
                            ->where('end_date', '>=', $validated['start_date']);
                    })
                    ->get();

                if ($existingSchedules->isNotEmpty()) {
                    $conflictingEmployees[$employeeId] = $existingSchedules->map(function ($schedule) {
                        return [
                            'schedule_id' => $schedule->id,
                            'start_date' => $schedule->start_date,
                            'end_date' => $schedule->end_date,
                        ];
                    })->toArray();

                    Log::warning('Schedule conflict found for employee', [
                        'employee_id' => $employeeId,
                        'conflicting_schedules' => $conflictingEmployees[$employeeId],
                    ]);
                }
            }

            // If there are conflicts, return an error with employee names and line breaks
            if (!empty($conflictingEmployees)) {
                $errorMessage = "The following employees have conflicting schedules:\n";
                foreach ($conflictingEmployees as $employeeId => $schedules) {
                    $employeeName = $employeeNames[$employeeId] ?? 'Unknown Employee';
                    $errorMessage .= "$employeeName has conflicts with schedules: ";
                    foreach ($schedules as $schedule) {
                        $errorMessage .= "Schedule ID {$schedule['schedule_id']} (from {$schedule['start_date']} to {$schedule['end_date']}), ";
                    }
                    $errorMessage = rtrim($errorMessage, ', ') . "\n";
                }
                $errorMessage = rtrim($errorMessage, "\n");

                return response()->json(['error' => $errorMessage], 422);
            }

            // Create the schedules
            $schedules = [];
            foreach ($validated['employees'] as $employeeId) {
                $schedules[] = Schedule::create([
                    'employee_id' => $employeeId,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'work_days' => $workDays,
                    'day_off' => $dayOff,
                    'holidays' => $holidays,
                ]);
            }

            Log::info('Schedules created successfully for employees: ' . implode(', ', $validated['employees']));
            return response()->json([
                'data' => $schedules,
                'message' => 'Schedule(s) created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Schedule store failed: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Schedule $schedule
     * @return JsonResponse
     */
    public function show(Schedule $schedule): JsonResponse
    {
        $schedule->load('employee');
        return response()->json([
            'data' => $schedule,
            'message' => 'Schedule retrieved successfully'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Schedule $schedule
     * @return JsonResponse
     */

public function update(Request $request, Schedule $schedule): JsonResponse
{
    try {
        Log::info('Schedule update request received: ' . json_encode($request->all()));

        // Validate the request
        $validated = $request->validate([
            'employees' => 'required|array|min:1',
            'employees.*' => 'exists:employees,employee_id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'work_days' => 'required|array|min:1',
            'work_days.*' => 'date|distinct',
            'day_off' => 'required|date',
            'holidays' => 'nullable|array',
            'holidays.*' => 'date|distinct',
        ]);

        // For simplicity, assuming single employee as per the original code
        $employeeId = $validated['employees'][0];

        // Create date range for the updated schedule
        $startDate = new \DateTime($validated['start_date']);
        $endDate = new \DateTime($validated['end_date']);
        $dateRange = [];
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        // Filter work_days, day_off, and holidays to ensure they fall within the date range
        $workDays = array_filter($validated['work_days'], fn($date) => in_array($date, $dateRange));
        $dayOff = in_array($validated['day_off'], $dateRange) ? $validated['day_off'] : null;
        $holidays = array_filter($validated['holidays'] ?? [], fn($date) => in_array($date, $dateRange));

        // Check for duplicate dates across work_days, day_off, and holidays
        $allAssignedDates = array_merge($workDays, [$dayOff], $holidays);
        if (count($allAssignedDates) !== count(array_unique($allAssignedDates))) {
            return response()->json(['error' => 'Dates must be unique across work_days, day_off, and holidays'], 422);
        }

        // Fetch employee name for the provided employee ID
        $employee = Employee::where('employee_id', $employeeId)->first();
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
        $employeeName = "{$employee->first_name} {$employee->last_name}";

        // Check for schedule conflicts for the employee, excluding the current schedule
        Log::info('Checking for schedule conflicts', [
            'employee_id' => $employeeId,
            'new_start_date' => $validated['start_date'],
            'new_end_date' => $validated['end_date'],
            'schedule_id' => $schedule->id,
        ]);

        $existingSchedules = Schedule::where('employee_id', $employeeId)
            ->where('id', '!=', $schedule->id) // Exclude the current schedule
            ->where(function ($query) use ($validated) {
                $query->where('start_date', '<=', $validated['end_date'])
                      ->where('end_date', '>=', $validated['start_date']);
            })
            ->get();

        if ($existingSchedules->isNotEmpty()) {
            $conflictingSchedules = $existingSchedules->map(function ($sched) {
                return [
                    'schedule_id' => $sched->id,
                    'start_date' => $sched->start_date,
                    'end_date' => $sched->end_date,
                ];
            })->toArray();

            Log::warning('Schedule conflict found for employee', [
                'employee_id' => $employeeId,
                'conflicting_schedules' => $conflictingSchedules,
            ]);

            // Build error message with employee name and line breaks
            $errorMessage = "The following employee has conflicting schedules:\n";
            $errorMessage .= "$employeeName has conflicts with schedules: ";
            foreach ($conflictingSchedules as $conflictingSchedule) {
                $errorMessage .= "Schedule ID {$conflictingSchedule['schedule_id']} (from {$conflictingSchedule['start_date']} to {$conflictingSchedule['end_date']}), ";
            }
            $errorMessage = rtrim($errorMessage, ', ') . "\n";
            $errorMessage = rtrim($errorMessage, "\n");

            return response()->json(['error' => $errorMessage], 422);
        }

        // Update the schedule
        $schedule->update([
            'employee_id' => $employeeId,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'work_days' => $workDays,
            'day_off' => $dayOff,
            'holidays' => $holidays,
        ]);

        Log::info('Schedule updated successfully for employee: ' . $employeeId);
        return response()->json([
            'data' => $schedule->fresh(), // Reload the schedule to get updated data
            'message' => 'Schedule updated successfully'
        ], 200);
    } catch (\Exception $e) {
        Log::error('Schedule update failed: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
        return response()->json(['error' => 'Internal Server Error'], 500);
    }
}
    /**
     * Remove the specified resource from storage.
     *
     * @param Schedule $schedule
     * @return JsonResponse
     */
    public function destroy(Schedule $schedule): JsonResponse
    {
        try {
            $scheduleId = $schedule->id;
            $schedule->delete();
            Log::info('Schedule deleted successfully: ID ' . $scheduleId);
            return response()->json([
                'message' => 'Schedule deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Schedule delete failed: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function mySchedules(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('email', $user->email)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee record not found for this user'
            ], 404);
        }

        $schedules = Schedule::where('employee_id', $employee->employee_id)
            ->with('employee') // Eager load employee relationship
            ->orderBy('start_date', 'desc') // Get most recent first
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $schedules
        ]);
    }
}