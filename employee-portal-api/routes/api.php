<?php

use App\Http\Controllers\EmployeeController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/roles', fn () => response()->json(['data' => Role::orderBy('name')->get()]));
Route::get('/employees/active', [EmployeeController::class, 'active']);
Route::patch('/employees/{employee}/status', [EmployeeController::class, 'status']);
Route::apiResource('employees', EmployeeController::class);
