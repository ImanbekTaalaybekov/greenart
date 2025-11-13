<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::post('/auth', [AuthController::class, 'auth']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/user/request-phone-verification', [AuthController::class, 'requestPhoneVerification'])->middleware('auth:sanctum');
Route::post('/user/confirm-phone-verification', [AuthController::class, 'confirmPhoneVerification'])->middleware('auth:sanctum');
Route::put('/user', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken'])->middleware('auth:sanctum');
Route::post('/user/fcm-token-remove', [AuthController::class, 'removeFcmToken'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin/ping', function (Request $request) {
    return response()->json([
        'message' => 'Админский доступ подтверждён.',
        'user' => $request->user(),
    ]);
});

Route::middleware(['auth:sanctum', 'role:worker'])->get('/worker/ping', function (Request $request) {
    return response()->json([
        'message' => 'Доступ работника подтверждён.',
        'user' => $request->user(),
    ]);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::post('/orders/{order}/assign-worker', [OrderController::class, 'assignWorker']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
});