<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkTeamController;
use App\Http\Controllers\User\ProfileImageController;

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
Route::post('work-teams', [WorkTeamController::class, 'store']);
Route::post('/users/{id}/profile-image', [ProfileImageController::class, 'update']);
// Rutas protegidas con Sanctum
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    Route::middleware('role:admin')->get('/admin/dashboard', function () {
        return response()->json(['message' => 'Bienvenido, admin']);
    });

    Route::middleware('role:sub_admin')->get('/grupo/dashboard', function () {
        return response()->json(['message' => 'Bienvenido, jefe de grupo']);
    });

    Route::post('/users', [UserController::class, 'store']);
});