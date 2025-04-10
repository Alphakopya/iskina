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

            $validated = $request->validate([
                'employees' => 'required|array|min:1',
                'employees.*' => 'exists:employees,employee_id', // Correct for employee_id
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'work_days' => 'required|array|min:1',
                'work_days.*' => 'date|distinct',
                'day_off' => 'required|date',
                'holidays' => 'nullable|array',
                'holidays.*' => 'date|distinct',
            ]);

            $startDate = new \DateTime($validated['start_date']);
            $endDate = new \DateTime($validated['end_date']);
            $dateRange = [];
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $dateRange[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }

            $workDays = array_filter($validated['work_days'], fn($date) => in_array($date, $dateRange));
            $dayOff = in_array($validated['day_off'], $dateRange) ? $validated['day_off'] : null;
            $holidays = array_filter($validated['holidays'], fn($date) => in_array($date, $dateRange));

            $allAssignedDates = array_merge($workDays, [$dayOff], $holidays);
            if (count($allAssignedDates) !== count(array_unique($allAssignedDates))) {
                return response()->json(['error' => 'Dates must be unique across work_days, day_off, and holidays'], 422);
            }

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

            // For single employee, use: 'employee_id' => 'required|exists:employees,employee_id'
            // Then: $validated['employee_id'] instead of $validated['employees'][0]

            $schedule->update([
                'employee_id' => $validated['employees'][0], // Assuming single employee for simplicity
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'work_days' => $validated['work_days'],
                'day_off' => $validated['day_off'],
                'holidays' => $validated['holidays'],
            ]);

            Log::info('Schedule updated successfully for employee: ' . $validated['employees'][0]);
            return response()->json([
                'data' => $schedule,
                'message' => 'Schedule updated successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Schedule update failed: ' . $e->getMessage());
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