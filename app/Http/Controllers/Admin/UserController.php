<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WorkTeam;
use App\Models\WorkSchedule;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // Solo admin o sub_jefe pueden crear usuarios
        $creator = Auth::user();
        if (! $creator->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'No tienes permisos para crear usuarios.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:20',
            'team_id' => 'nullable|exists:work_teams,id',
            'role' => 'required|in:moderator,worker',
        ]);

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
        ]);

        // Asignar rol
        $user->assignRole($request->role);

        // Asignar equipo de trabajo (si fue enviado)
        if ($request->filled('team_id')) {
            $team = WorkTeam::find($request->team_id);
            $user->workTeams()->attach($team->id);

            // Buscar horario base del equipo
            $baseSchedule = WorkSchedule::where('work_team_id', $team->id)->first();

            if ($baseSchedule) {
                $user->workSchedules()->attach($baseSchedule->id, [
                    'days' => json_encode(['lunes', 'martes', 'miércoles', 'jueves', 'viernes']),
                    'assigned_by' => $creator->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'user' => $user->load('roles', 'workTeams', 'workSchedules')
        ], 201);
    }
}
