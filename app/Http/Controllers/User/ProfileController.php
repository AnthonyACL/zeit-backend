<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Muestra los datos del perfil del usuario autenticado.
     * Corresponde a GET /api/profile
     */
    public function show()
    {
        // Carga las relaciones del usuario autenticado
        $user = Auth::user()->load('roles', 'workTeams', 'workSchedules'); 

        return response()->json(['user' => $user], 200);
    }
    
    /**
     * Actualiza los datos del usuario autenticado.
     * Corresponde a PUT /api/profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Reglas de validación adaptadas para el propio usuario
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'dni' => [
                'nullable', 
                'string', 
                'max:8',
                // Ignora el DNI del usuario actual para la unicidad
                Rule::unique('users', 'dni')->ignore($user->id), 
            ],
            'email' => [
                'sometimes', 
                'required', 
                'email', 
                // Ignora el email del usuario actual para la unicidad
                Rule::unique('users', 'email')->ignore($user->id), 
            ],
            'phone' => 'nullable|string|max:20',
            // No incluye 'role' ni 'team_id'. Permite actualizar contraseña.
            'password' => 'nullable|min:6|confirmed', 
        ];

        $validated = $request->validate($rules);
        $dataToUpdate = $validated;
        
        // Hashear la nueva contraseña si se proporciona
        if (isset($validated['password'])) {
            $dataToUpdate['password'] = bcrypt($validated['password']);
        } else {
            // Evita actualizar el campo 'password' si no se envió
            unset($dataToUpdate['password']); 
        }

        $user->update($dataToUpdate);
        
        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => $user->load('roles', 'workTeams')
        ], 200);
    }
    
    /**
     * Actualiza la imagen de perfil del usuario autenticado.
     * Corresponde a POST /api/profile/image
     */
    public function updateProfileImage(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        
        // Elimina la imagen anterior
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $path = $request->file('profile_image')->store('profile_images', 'public');

        $user->update([
            'profile_image' => $path,
        ]);

        return response()->json([
            'message' => 'Imagen de perfil actualizada correctamente.',
            'image_url' => asset('storage/' . $user->profile_image),
        ]);
    }
}
