<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AnnouncementController;

Route::post('/auth', [AuthController::class, 'auth']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/user/request-phone-verification', [AuthController::class, 'requestPhoneVerification'])->middleware('auth:sanctum');
Route::post('/user/confirm-phone-verification', [AuthController::class, 'confirmPhoneVerification'])->middleware('auth:sanctum');
Route::put('/user', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken'])->middleware('auth:sanctum');
Route::post('/user/fcm-token-remove', [AuthController::class, 'removeFcmToken'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/ping', function (Request $request) {
        return response()->json([
            'message' => 'Админский доступ подтверждён.',
            'user' => $request->user(),
        ]);
    });

    Route::patch('/orders/{order}/classification', [OrderController::class, 'classify']);
    Route::post('/orders/{order}/assign-worker', [OrderController::class, 'assignWorker']);
    Route::post('/clients/{client}/default-worker', [ClientWorkerController::class, 'setDefaultWorker']);
});

Route::middleware(['auth:sanctum', 'role:worker'])->prefix('worker')->group(function () {
    Route::get('/ping', function (Request $request) {
        return response()->json([
            'message' => 'Доступ работника подтверждён.',
            'user' => $request->user(),
        ]);
    });

    Route::get('/tasks', [WorkerTaskController::class, 'tasks']);
    Route::post('/tasks/{order}/report', [WorkerTaskController::class, 'storeReport']);
    Route::get('/reports', [WorkerTaskController::class, 'reports']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
    Route::post('/announcements/{announcement}/read', [AnnouncementController::class, 'markRead']);
});
