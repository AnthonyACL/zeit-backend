<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login de usuario con generación de token Sanctum
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'password'  => 'required',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Validar si el usuario está activo
        if ($user->status == 0) {
            return response()->json([
                'message' => 'Tu cuenta está inactiva, comunícate con el administrador.',
            ], 403);
        }

        // Permitir solo 1 dispositivo a la vez
        $user->tokens()->delete();

        // Actualizar ubicación si viene del frontend
        if ($request->latitude && $request->longitude) {
            $user->update([
                'latitude'  => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        }

        // Crear token nuevo
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'token'   => $token,
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'last_name'     => $user->last_name,
                'email'         => $user->email,
                'dni'           => $user->dni,
                'phone'         => $user->phone,
                'profile_image' => $user->profile_image,
                'latitude'      => $user->latitude,
                'longitude'     => $user->longitude,
                'status'        => $user->status,
                'roles'         => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    /**
     * Información del usuario autenticado
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user'        => $user,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();
        $user->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Ubicación actualizada correctamente',
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
        ]);
    }

    
}
