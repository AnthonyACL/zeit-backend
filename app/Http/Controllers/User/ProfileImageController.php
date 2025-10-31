<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ProfileImageController extends Controller
{
    public function update(Request $request, $id)
    {
        // Validar que se suba una imagen
        $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = User::findOrFail($id);

        // Elimina la imgane anterior 
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $path = $request->file('profile_image')->store('profile_images', 'public');

        $user->update([
            'profile_image' => $path,
        ]);

        return response()->json([
            'message' => 'Imagen de perfil actualizada correctamente.',
            'image_url' => asset('storage/' . $path),
        ]);
    }
}
