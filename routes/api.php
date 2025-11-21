<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderPhotoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AnnouncementController;

Route::post('/auth', [AuthController::class, 'auth']);
Route::post('/register', [AuthController::class, 'register']);
Route::put('/user', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::patch('/orders/{order}/classification', [OrderController::class, 'classify']);
    Route::post('/orders/{order}/assign-worker', [OrderController::class, 'assignWorker']);
    Route::post('/clients/{client}/default-worker', [ClientWorkerController::class, 'setDefaultWorker']);
});

Route::middleware(['auth:sanctum', 'role:worker'])->prefix('worker')->group(function () {
    Route::get('/tasks', [WorkerTaskController::class, 'tasks']);
    Route::post('/tasks/{order}/report', [WorkerTaskController::class, 'storeReport']);
    Route::get('/reports', [WorkerTaskController::class, 'reports']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('order-photos', OrderPhotoController::class)->only(['destroy']);
    Route::apiResource('announcements', AnnouncementController::class);
    Route::apiResource('orders', OrderController::class);
});
