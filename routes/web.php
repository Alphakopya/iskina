<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\FingerprintController;
use App\Models\Schedule;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PayrollController;
Route::get('/', function () {
    return Auth::check() ? view('app') : view('auth.login');
});


Route::get('/user', [AuthController::class, 'user'])->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::view('/employee', 'employee-list')->name('employee.list');
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::view('/employee/new', 'employee-new')->name('employee.new');
    Route::get('/get-branches', [BranchController::class, 'getBranches']);
    Route::get('/employee-update/{id}', function ($id) {
        return view('employee-update', ['id' => $id]);
    });
    
    Route::view('/branch', 'branch-list')->name('branch.list');
    Route::view('/branch/new', 'branch-new')->name('branch.new');
    Route::get('/branch-update/{id}', function ($id) {
        return view('branch-update', ['id' => $id]);
    });
    
    Route::get('/attendance', function () {
        return view('attendance-list');
    })->name('attendance.list');
    Route::get('/attendance-view/{id}', function () {
        return view('attendance-view');
    })->name('attendance.view');
    Route::get('/api/attendance', [AttendanceController::class, 'index']);
    Route::get('/api/attendance/{id}', [AttendanceController::class, 'show']);
    Route::view('/my-attendance', 'my-attendance')->name('my.attendance');
    Route::get('/my-attendance/list', [AttendanceController::class, 'fetchMyAttendance'])->name('api.my.attendance');
    Route::post('/device/mode', [AttendanceController::class, 'updateDeviceMode']);
    Route::get('/device-mode', [AttendanceController::class, 'getDeviceMode']);
    Route::get('/fingerprint/employees/{employeeId}', [FingerprintController::class, 'getEmployee']);
    Route::get('/fingerprint', [FingerprintController::class, 'fingerprintIndex']);
    Route::view('/fingerprint/new', 'fingerprint-new')->name('fingerprint.new');
    Route::get('/employees/by-branch', [EmployeeController::class, 'byBranch']);
    Route::post('/fingerprint/select', [FingerprintController::class, 'select'])->name('fingerprint.select');
    Route::post('/fingerprint/register', [FingerprintController::class, 'register'])->name('fingerprint.register');
    Route::get('/fingerprint/status', [FingerprintController::class, 'getEnrollmentStatus'])->name('fingerprint.status');    
    Route::get('/fingerprint/new', fn() => view('fingerprint-new'))->name('fingerprint.new');
    Route::post('/fingerprint/unselect', [FingerprintController::class, 'unselect'])->name('fingerprint.unselect');
    Route::get('/fingerprint/list', [FingerprintController::class, 'index'])->name('fingerprint.list');
    Route::post('/fingerprint/select-for-edit', [FingerprintController::class, 'selectForEdit'])->name('fingerprint.selectForEdit');
    Route::get('/fingerprint/edit', function () {
        return view('fingerprint-edit');
    })->name('fingerprint.edit');
    Route::post('fingerprint/delete', [FingerprintController::class, 'deleteFingerprint'])->name('fingerprint.delete');
    Route::view('/attendance/form', 'attendance-form')->name('attendance.form');

    Route::view('/leaves/list', 'leaves-list')->name('leaves.list');
    Route::view('/leaves/new', 'leaves-new')->name('leaves.new');
    Route::view('/leaves', 'leaves')->name('leaves');
    Route::get('/my-leaves', [LeaveController::class, 'myLeaves']);

    Route::view('/schedules/list', 'schedule-list')->name('schedules.list');
    Route::view('/schedules/new', 'schedule-new')->name('schedules.new');
    Route::get('/branches/filter', [BranchController::class, 'getBranches']);
    Route::get('/employees/by-branch', [EmployeeController::class, 'getEmployeesByBranch']);
    Route::view('/schedules', 'schedules')->name('schedules');
    Route::get('/my-schedules', [ScheduleController::class, 'mySchedules']);
    Route::get('/schedule-view/{schedule}', function (Schedule $schedule) {
        $schedule->load('employee');
        return view('schedule-view', compact('schedule'));
    });
    Route::get('/schedule-update/{schedule}', fn(Schedule $schedule) => view('schedule-update', compact('schedule')));
    
    Route::get('/payrolls', function () {
        return view('payrolls');
    })->name('payrolls');
    Route::get('/payrolls/list', [PayrollController::class, 'index']);
    Route::post('/payrolls', [PayrollController::class, 'store']);
    Route::get('/payrolls/{id}', [PayrollController::class, 'show']);
    Route::put('/payrolls/{id}', [PayrollController::class, 'update']);
    Route::delete('/payrolls/{id}', [PayrollController::class, 'destroy']);
    Route::post('/payrolls/batch', [PayrollController::class, 'batchStore']);
    Route::get('/employees/by-branch', [EmployeeController::class, 'byBranch']);
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/profile/show', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::view('/profile', 'profile')->name('profile');
    Route::get('/my-pay', [PayrollController::class, 'myPay'])->name('my.pay');
    Route::get('/my-payrolls', [PayrollController::class, 'myPayData']);
    Route::get('/employee-info', [EmployeeController::class, 'getEmployeeInfo']);
});