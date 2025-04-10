<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Http\Requests\StorePayrollRequest;
use App\Http\Requests\UpdatePayrollRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;

class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payrolls = Payroll::with('employee')->paginate(10);
        return response()->json(['status' => 'success', 'data' => $payrolls]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string|exists:employees,employee_id',
            'basic_salary' => 'required|numeric|min:0',
            'overtime_pay' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,processed,paid',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $net_salary = $request->basic_salary + ($request->overtime_pay ?? 0) - ($request->deductions ?? 0);

        $payroll = Payroll::create([
            'employee_id' => $request->employee_id,
            'basic_salary' => $request->basic_salary,
            'overtime_pay' => $request->overtime_pay ?? 0,
            'deductions' => $request->deductions ?? 0,
            'net_salary' => $net_salary,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? 'pending',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Payroll created successfully', 'data' => $payroll->load('employee')], 201);
    }

    public function show($id)
    {
        $payroll = Payroll::with('employee')->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $payroll]);
    }

    public function update(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);

        // Validate the request
        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|string|exists:employees,employee_id',
            'basic_salary' => 'sometimes|numeric|min:0',
            'overtime_pay' => 'sometimes|numeric|min:0', // Changed from nullable to sometimes to ensure validation
            'deductions' => 'sometimes|numeric|min:0',   // Changed from nullable to sometimes to ensure validation
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:pending,processed,paid',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for payroll update', [
                'id' => $id,
                'errors' => $validator->errors(),
                'request' => $request->all(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Prepare the data for update
        $data = $request->only(['employee_id', 'basic_salary', 'overtime_pay', 'deductions', 'start_date', 'end_date', 'status']);

        // Recalculate net_salary if any of the financial fields are updated
        if ($request->has('basic_salary') || $request->has('overtime_pay') || $request->has('deductions')) {
            $basic_salary = $request->basic_salary ?? $payroll->basic_salary;
            $overtime_pay = $request->overtime_pay ?? $payroll->overtime_pay;
            $deductions = $request->deductions ?? $payroll->deductions;

            // Ensure values are non-negative
            $basic_salary = max(0, $basic_salary);
            $overtime_pay = max(0, $overtime_pay);
            $deductions = max(0, $deductions);

            // Cap deductions to avoid negative net salary
            $maxDeductions = $basic_salary + $overtime_pay;
            $deductions = min($deductions, $maxDeductions);

            // Calculate net salary
            $data['net_salary'] = $basic_salary + $overtime_pay - $deductions;
            $data['net_salary'] = max(0, $data['net_salary']); // Ensure net_salary is not negative

            // Update the data array with validated values
            $data['basic_salary'] = $basic_salary;
            $data['overtime_pay'] = $overtime_pay;
            $data['deductions'] = $deductions;
        }

        // Log the data being updated
        Log::info('Updating payroll record', [
            'id' => $id,
            'data' => $data,
            'request' => $request->all(),
        ]);

        // Update the payroll record
        $payroll->update($data);

        // Reload the employee relationship
        $payroll->load('employee');

        return response()->json(['status' => 'success', 'message' => 'Payroll updated successfully', 'data' => $payroll]);
    }

    public function destroy($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->delete();
        return response()->json(['status' => 'success', 'message' => 'Payroll deleted successfully']);
    }

    /**
     * Batch store payrolls for multiple employees based on attendance and schedule.
     */
    public function batchStore(Request $request)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        'employees' => 'required|array|min:1',
        'employees.*' => 'string|exists:employees,employee_id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    if ($validator->fails()) {
        Log::error('Validation failed for batchStore', ['errors' => $validator->errors()]);
        return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
    }

    // Parse the date range
    $startDate = Carbon::parse($request->start_date);
    $endDate = Carbon::parse($request->end_date);

    $payrolls = [];
    $hourlyRate = 20; // Example: P80/hour
    $standardHoursPerDay = 8;
    $overtimeRateMultiplier = 1.5;
    $absenceDeductionPerDay = $hourlyRate * $standardHoursPerDay; // P160/day

    foreach ($request->employees as $employee_id) {
        try {
            // Fetch attendance for the employee for the specified date range
            $attendances = Attendance::where('employee_id', $employee_id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            // Fetch schedule for the employee
            $schedule = Schedule::where('employee_id', $employee_id)
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->first();

            if (!$schedule) {
                Log::warning("No schedule found for employee {$employee_id} for the period {$startDate} to {$endDate}");
                continue; // Skip if no schedule found
            }

            // Calculate expected work days, excluding days off and holidays
            $workDays = collect($schedule->work_days);
            $daysOff = collect($schedule->day_off);
            $holidays = collect($schedule->holidays);

            $expectedWorkDays = $workDays->filter(function ($date) use ($startDate, $endDate, $daysOff, $holidays) {
                $date = Carbon::parse($date);
                if (!$date->between($startDate, $endDate)) {
                    return false;
                }
                $dateString = $date->toDateString();
                return !$daysOff->contains($dateString) && !$holidays->contains($dateString);
            })->count();

            // Calculate actual hours worked
            $totalRegularHours = 0;
            $totalOvertimeHours = 0;
            $actualWorkDays = 0;

            foreach ($attendances as $attendance) {
                if (!$attendance->time_in || !$attendance->time_out) {
                    Log::warning("Incomplete attendance record for employee {$employee_id} on {$attendance->date}");
                    continue;
                }

                $timeIn = Carbon::parse($attendance->time_in);
                $timeOut = Carbon::parse($attendance->time_out);

                // Validate time_out is after time_in
                if ($timeOut->lessThanOrEqualTo($timeIn)) {
                    Log::warning("Invalid attendance: time_out ({$timeOut}) is before or equal to time_in ({$timeIn}) for employee {$employee_id} on {$attendance->date}");
                    continue;
                }

                // Calculate total hours worked, accounting for breaks
                $breakDurationMinutes = 0;
                if ($attendance->break_in && $attendance->break_out) {
                    $breakIn = Carbon::parse($attendance->break_in);
                    $breakOut = Carbon::parse($attendance->break_out);

                    // Validate break_out is after break_in
                    if ($breakOut->greaterThan($breakIn)) {
                        $breakDurationMinutes = $breakOut->diffInMinutes($breakIn);
                    } else {
                        Log::warning("Invalid break times: break_out ({$breakOut}) is before break_in ({$breakIn}) for employee {$employee_id} on {$attendance->date}");
                    }
                }

                // Calculate total minutes worked, then convert to hours
                $minutesWorked = $timeOut->diffInMinutes($timeIn) - $breakDurationMinutes;
                $hoursWorked = $minutesWorked / 60;

                // Split into regular and overtime hours
                $regularHours = min($hoursWorked, $standardHoursPerDay);
                $overtimeHours = max(0, $hoursWorked - $standardHoursPerDay);

                $totalRegularHours += $regularHours;
                $totalOvertimeHours += $overtimeHours;
                $actualWorkDays++;
            }

            // Calculate absences
            $absences = max(0, $expectedWorkDays - $actualWorkDays);

            // Calculate payroll values
            $basicSalary = round($totalRegularHours * $hourlyRate, 2);
            $overtimePay = round($totalOvertimeHours * $hourlyRate * $overtimeRateMultiplier, 2);
            $maxDeductions = $basicSalary + $overtimePay; // Cap deductions to avoid negative net salary
            $deductions = min($absences * $absenceDeductionPerDay, $maxDeductions);
            $deductions = round($deductions, 2);

            // Ensure all values are non-negative
            $basicSalary = max(0, $basicSalary);
            $overtimePay = max(0, $overtimePay);
            $deductions = max(0, $deductions);

            $netSalary = $basicSalary + $overtimePay - $deductions;
            $netSalary = max(0, $netSalary);

            // Log the calculation details
            Log::info("Payroll calculation for employee {$employee_id}", [
                'regular_hours' => $totalRegularHours,
                'overtime_hours' => $totalOvertimeHours,
                'basic_salary' => $basicSalary,
                'overtime_pay' => $overtimePay,
                'deductions' => $deductions,
                'net_salary' => $netSalary,
                'expected_work_days' => $expectedWorkDays,
                'actual_work_days' => $actualWorkDays,
                'absences' => $absences,
            ]);

            // Create payroll record
            $payroll = Payroll::create([
                'employee_id' => $employee_id,
                'basic_salary' => $basicSalary,
                'overtime_pay' => $overtimePay,
                'deductions' => $deductions,
                'net_salary' => $netSalary,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'pending',
            ]);

            $payrolls[] = $payroll;
        } catch (\Exception $e) {
            Log::error("Error processing payroll for employee {$employee_id}", [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            continue; // Skip this employee and continue with the next
        }
    }

    if (empty($payrolls)) {
        return response()->json(['status' => 'error', 'message' => 'No payrolls were created. Check logs for details.'], 400);
    }

    return response()->json(['status' => 'success', 'message' => 'Payrolls created successfully', 'data' => $payrolls], 201);
}

public function myPay()
    {
        return view('my-pay');
    }

    public function myPayData(Request $request)
    {
        $user = Auth::user();
        
        // Find the employee record associated with the authenticated user
        $employee = Employee::where('email', $user->email)->first();
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }

        // Fetch payroll records for the employee
        $payrolls = Payroll::where('employee_id', $employee->employee_id)
            ->with('employee')
            ->paginate(10);

        return response()->json(['status' => 'success', 'data' => $payrolls]);
    }
}