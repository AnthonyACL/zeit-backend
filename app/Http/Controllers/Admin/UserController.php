<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WorkTeam;
use App\Models\WorkSchedule;
use App\Models\UserWorkDay;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $creator = Auth::user();

        // Solo admin o sub_admin pueden crear usuarios
        if (! $creator->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'No tienes permisos para crear usuarios.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'dni' => 'nullable|string|max:8|unique:users,dni',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:20',
            'team_id' => 'nullable|exists:work_teams,id',
            'role' => 'required|in:moderator,worker',
        ]);

        // Crear usuario
        $user = User::create([
            'name' => $validated['name'],
            'last_name' => $validated['last_name'] ?? null,
            'dni' => $validated['dni'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // Asignar rol
        $user->assignRole($validated['role']);

        // Si tiene un equipo asignado
        if ($request->filled('team_id')) {
            $team = WorkTeam::find($validated['team_id']);
            $user->workTeams()->attach($team->id);

            // Buscar el horario base del equipo
            $baseSchedule = WorkSchedule::where('work_team_id', $team->id)->first();

            if ($baseSchedule) {
                // Crear una copia del horario base solo para este usuario
                $personalSchedule = WorkSchedule::create([
                    'name' => $baseSchedule->name . ' (Usuario: ' . $user->name . ')',
                    'work_team_id' => $team->id,
                    'start_time' => $baseSchedule->start_time,
                    'end_time' => $baseSchedule->end_time,
                ]);

                // Asignar al usuario su horario personal
                UserWorkDay::create([
                    'user_id' => $user->id,
                    'work_team_id' => $team->id,
                    'work_schedule_id' => $personalSchedule->id,
                    'days' => json_encode(['lunes', 'martes', 'miércoles', 'jueves', 'viernes']),
                    'assigned_by' => $creator->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Usuario creado correctamente con su propio horario (basado en el equipo).',
            'user' => $user->load('roles', 'workTeams', 'workSchedules')
        ], 201);
    }

    public function options()
    {
        $roles = Role::select('id', 'name')->get();
        $teams = WorkTeam::select('id', 'name')->get();

        return response()->json([
            'roles' => $roles,
            'teams' => $teams,
        ]);
    }
}
