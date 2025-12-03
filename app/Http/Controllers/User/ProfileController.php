<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Mostrar datos del perfil
     */
    public function show()
    {
        $user = Auth::user()->load('roles', 'workTeams', 'workSchedules');

        return response()->json([
            'user' => $user
        ], 200);
    }

    /**
     * Actualizar datos del perfil (sin permitir cambiar contraseña)
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'institution' => 'nullable|string|max:255',
            'career' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            // password NO se permite
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => $user
        ], 200);
    }

    /**
     * Actualizar imagen de perfil
     */
    public function updateProfileImage(Request $request)
    {
        $user = Auth::user();

        // 1. Validation: Ensures the file is present, an image, and under 2MB.
        $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $file = $request->file('profile_image');
        $folder = 'image_profile';
        
        // Generate a unique filename to prevent collisions and security issues
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // 2. Delete the old image if one exists
        // We use Storage::disk('public')->delete() as the path stored is relative to the disk root.
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // 3. Store the new image using the 'public' disk
        // storeAs() handles moving, file path generation, and stream handling efficiently.
        // It returns the path relative to the disk (e.g., 'image_profile/filename.jpg').
        $path = $file->storeAs($folder, $filename, 'public');

        // 4. Update database with the relative path
        $user->profile_image = $path;
        $user->save();

        // 5. Return response
        return response()->json([
            'message' => 'Imagen de perfil actualizada correctamente.',
            // Use Storage::url() to generate the accessible public URL
            'image_url' => Storage::disk('public')->url($user->profile_image),
            'user' => $user, // Return updated user for convenience
        ]);
    }
}
