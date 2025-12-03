<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkTeamController;
use App\Http\Controllers\User\ProfileImageController;
use App\Http\Controllers\User\ProjectController;
use App\Http\Controllers\User\TaskController;
use App\Http\Controllers\User\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas privadas
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-location', [AuthController::class, 'updateLocation']);

    // Perfil personal
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('/image', [ProfileController::class, 'updateProfileImage']);
    });
});

// Admin y sub_admin
Route::middleware(['auth:sanctum', 'role:admin|sub_admin'])
    ->prefix('admin')
    ->group(function () {
        Route::apiResource('users', UserController::class)
            ->except(['create', 'edit']);
        Route::get('/users-options', [UserController::class, 'getCreationOptions']);
        Route::apiResource('work-teams', WorkTeamController::class)
            ->except(['create', 'edit']);
    });

// Moderator (solo lectura)
Route::middleware(['auth:sanctum', 'role:moderator'])
    ->prefix('moderator')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::get('/work-teams', [WorkTeamController::class, 'index']);
        Route::get('/work-teams/{id}', [WorkTeamController::class, 'show']);
    });




