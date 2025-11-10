<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth', [AuthController::class, 'auth']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/user/request-phone-verification', [AuthController::class, 'requestPhoneVerification'])->middleware('auth:sanctum');
Route::post('/user/confirm-phone-verification', [AuthController::class, 'confirmPhoneVerification'])->middleware('auth:sanctum');
Route::put('/user', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken'])->middleware('auth:sanctum');
Route::post('/user/fcm-token-remove', [AuthController::class, 'removeFcmToken'])->middleware('auth:sanctum');
