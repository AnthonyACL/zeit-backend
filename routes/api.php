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

// RUTAS PÚBLICAS (NO REQUIEREN AUTENTICACIÓN)
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// RUTAS DE COLLABORADOR
Route::middleware(['auth:sanctum', 'role:collaborator'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

});

//ADMINISTRADOR
Route::middleware(['auth:sanctum', 'role:admin|sub_admin|moderator'])->prefix('admin')->group(function () {
    
    // Rutas de administración de usuarios (usa UserController)
    Route::get('/users/options', [UserController::class, 'getCreationOptions']);
    Route::resource('users', UserController::class)->except(['create', 'edit']); 

    Route::resource('work-teams', WorkTeamController::class)->except(['create', 'edit']);
});

//PERFIL
Route::middleware(['auth:sanctum'])->prefix('profile')->group(function () {
    
    // Rutas de perfil personal (usa ProfileController)
    Route::get('/', [ProfileController::class, 'show']); 
    Route::put('/', [ProfileController::class, 'update']);
    Route::post('/image', [ProfileController::class, 'updateProfileImage']); 
});

