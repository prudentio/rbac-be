<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::middleware('auth:api')->group(function () {
    Route::get('/auth/user-info', [AuthController::class, 'userInfo']);
    require __DIR__.'/department/index.php';
    require __DIR__.'/role/index.php';
    require __DIR__.'/user/index.php';
    require __DIR__.'/application/index.php';
});

Route::post('/auth/access-token', [AuthController::class, 'login']);