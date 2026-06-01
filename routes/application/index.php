<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Application\ApplicationController;
use App\Http\Controllers\Api\Application\CategoryController;
use App\Http\Controllers\Api\Application\Access\MatrixController;
use App\Http\Controllers\Api\Application\Access\DepartmentController;
use App\Http\Controllers\Api\Application\Access\RoleController;
use App\Http\Controllers\Api\Application\Access\UserController;

Route::middleware('auth:api')->group(function () {

    Route::prefix('applications')->group(function () {

        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/{id}', [CategoryController::class, 'show']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::patch('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });

        Route::prefix('access')->group(function () {

            Route::prefix('departments')->group(function () {
                Route::get('/', [DepartmentController::class, 'departmentIndex']);
                Route::get('/{id}', [DepartmentController::class, 'departmentShow']);
                Route::post('/', [DepartmentController::class, 'departmentStore']);
                Route::patch('/{id}', [DepartmentController::class, 'departmentUpdate']);
                Route::delete('/{id}', [DepartmentController::class, 'departmentDestroy']);
            });

            Route::prefix('roles')->group(function () {
                Route::get('/', [RoleController::class, 'roleIndex']);
                Route::get('/{id}', [RoleController::class, 'roleShow']);
                Route::post('/', [RoleController::class, 'roleStore']);
                Route::patch('/{id}', [RoleController::class, 'roleUpdate']);
                Route::delete('/{id}', [RoleController::class, 'roleDestroy']);
            });

            Route::prefix('users')->group(function () {
                Route::get('/', [UserController::class, 'userIndex']);
                Route::get('/{id}/applications', [UserController::class, 'applicationIndex']);
                Route::get('/{id}', [UserController::class, 'userShow']);
                Route::post('/', [UserController::class, 'userStore']);
                Route::patch('/{id}', [UserController::class, 'userUpdate']);
                Route::delete('/{id}', [UserController::class, 'userDestroy']);
            });

            Route::prefix('matrix')->group(function () {
                Route::get('/', [MatrixController::class, 'index']);
                Route::get('/{id}', [MatrixController::class, 'show']);
            });
        });

        Route::get('/', [ApplicationController::class, 'index']);
        Route::get('/{id}', [ApplicationController::class, 'show']);
        Route::post('/', [ApplicationController::class, 'store']);
        Route::patch('/{id}', [ApplicationController::class, 'update']);
        Route::delete('/{id}', [ApplicationController::class, 'destroy']);
    });
});