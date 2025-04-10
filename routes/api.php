<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\FingerprintController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::apiResource('branches', BranchController::class);
Route::apiResource('employees', EmployeeController::class);
Route::apiResource('leaves', LeaveController::class);
Route::apiResource('schedules', ScheduleController::class);
Route::apiResource('fingerprints', FingerprintController::class);
Route::get('/employees', [EmployeeController::class, 'index']);
Route::get('/fingerprints', [FingerprintController::class, 'index']);
Route::post('/fingerprint', [FingerprintController::class, 'handleFingerprint']);
Route::post('/leaves/{leave}/accept', [LeaveController::class, 'accept']);
Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject']);