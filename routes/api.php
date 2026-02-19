<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientWorkerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPhotoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkerTaskController;
use App\Http\Controllers\WorkVisitController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AnnouncementPhotoController;

Route::post('/auth', [AuthController::class, 'auth']);
Route::post('/register', [AuthController::class, 'register']);
Route::put('/user', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::patch('/orders/{order}/classification', [OrderController::class, 'classify']);
    Route::post('/orders/{order}/assign-worker', [OrderController::class, 'assignWorker']);
    Route::post('/clients/{client}/default-worker', [ClientWorkerController::class, 'setDefaultWorker']);
    Route::get('/clients', [UserController::class, 'clients']);
    Route::get('/workers', [UserController::class, 'workers']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::get('/workers/{worker}/salary', [UserController::class, 'workerSalary']);
    Route::get('/visits', [WorkVisitController::class, 'indexAdmin']);
});

Route::middleware(['auth:sanctum', 'role:worker'])->prefix('worker')->group(function () {
    Route::get('/tasks', [WorkerTaskController::class, 'tasks']);
    Route::post('/tasks/{order}/report', [WorkerTaskController::class, 'storeReport']);
    Route::get('/reports', [WorkerTaskController::class, 'reports']);
    Route::get('/visits', [WorkVisitController::class, 'index']);
    Route::post('/visits', [WorkVisitController::class, 'store']);
    Route::delete('/visits/{visit}', [WorkVisitController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('order-photos', OrderPhotoController::class)->only(['destroy']);
    Route::apiResource('announcement-photos', AnnouncementPhotoController::class)->only(['destroy']);
    Route::apiResource('announcements', AnnouncementController::class);
    Route::apiResource('orders', OrderController::class);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chats', [ChatController::class, 'store']);
    Route::get('/chats/{chat}/messages', [ChatController::class, 'messages']);
    Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage']);
});

Route::middleware(['auth:sanctum', 'role:admin,client'])->group(function () {
    Route::get('/workers/{worker}/schedule', [UserController::class, 'workerSchedule']);
});

