<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

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

// Rutas protegidas con Sanctum
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Ejemplo de ruta protegida por rol con Spatie
    Route::middleware('role:admin')->get('/admin/dashboard', function () {
        return response()->json(['message' => 'Bienvenido, admin']);
    });

    Route::middleware('role:jefe_grupo')->get('/grupo/dashboard', function () {
        return response()->json(['message' => 'Bienvenido, jefe de grupo']);
    });
});