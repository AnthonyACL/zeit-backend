<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Validation\Rule; 
use App\Models\User;
use App\Models\WorkTeam;
use App\Models\WorkSchedule;
use App\Models\UserWorkDay;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with([
            'roles:name',
            'workTeams:name',
            'workSchedules:id,name,start_time,end_time,work_team_id'
        ])->get(['id', 'name', 'last_name', 'dni', 'email', 'phone']);

        return response()->json([
            'message' => 'Lista de usuarios obtenida correctamente.',
            'users' => $users
        ], 200);
    }

    /**
     * Almacena un nuevo usuario en la base de datos.
     */
    public function store(Request $request)
    {
        $creator = Auth::user();

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
            'role' => 'required|in:moderator,collaborator',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'last_name' => $validated['last_name'] ?? null,
            'dni' => $validated['dni'] ?? null,
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']), 
            'phone' => $validated['phone'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        if ($request->filled('team_id')) {
            $team = WorkTeam::find($validated['team_id']);
            $user->workTeams()->syncWithoutDetaching([$team->id]); 

            $baseSchedule = WorkSchedule::where('work_team_id', $team->id)->first();

            if ($baseSchedule) {
                $personalSchedule = WorkSchedule::create([
                    'name' => $baseSchedule->name . ' (Usuario: ' . $user->name . ')',
                    'work_team_id' => $team->id,
                    'start_time' => $baseSchedule->start_time,
                    'end_time' => $baseSchedule->end_time,
                ]);

                UserWorkDay::create([
                    'user_id' => $user->id,
                    'work_team_id' => $team->id,
                    'work_schedule_id' => $personalSchedule->id,
                    'days' => ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'], 
                    'assigned_by' => $creator->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Usuario creado correctamente con su propio horario (basado en el equipo).',
            'user' => $user->load('roles', 'workTeams', 'workSchedules')
        ], 201);
    }
    
    /**
     * Muestra los datos de un usuario específico por ID (solo para administración).
     */
    public function show($id)
    { 
        $user = User::with('roles', 'workTeams', 'workSchedules')->findOrFail($id);
        
        // Verifica si el usuario autenticado tiene rol de administración o moderador
        if (! Auth::user()->hasAnyRole(['admin', 'sub_admin', 'moderator'])) {
             return response()->json(['message' => 'Acceso denegado para ver otros perfiles.'], 403);
        }

        return response()->json(['user' => $user], 200);
    }

    
    /**
     * Actualiza los datos de un usuario específico por ID (solo para administración).
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Solo admins y sub-admins pueden actualizar otros usuarios
        if (! Auth::user()->hasAnyRole(['admin', 'sub_admin'])) {
             return response()->json(['message' => 'No tienes permisos para actualizar a este usuario.'], 403);
        }
        
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'dni' => [
                'nullable', 
                'string', 
                'max:8',
                Rule::unique('users', 'dni')->ignore($user->id), 
            ],
            'email' => [
                'sometimes', 
                'required', 
                'email', 
                Rule::unique('users', 'email')->ignore($user->id), 
            ],
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|required|in:moderator,collaborator',
            'team_id' => 'nullable|exists:work_teams,id',
            'password' => 'nullable|min:6|confirmed', 
        ];

        $validated = $request->validate($rules);
        $dataToUpdate = $validated;
        
        if (isset($validated['password'])) {
            $dataToUpdate['password'] = bcrypt($validated['password']);
        } else {
            unset($dataToUpdate['password']); 
        }

        $user->update($dataToUpdate);
        
        // Actualizar Rol
        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }
        
        // Actualizar Equipo
        if (array_key_exists('team_id', $validated)) { // Usar array_key_exists para manejar el valor 'null'
            if (is_null($validated['team_id'])) {
                $user->workTeams()->sync([]); // Desasociar
            } else {
                $user->workTeams()->sync([$validated['team_id']]);
            }
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'user' => $user->load('roles', 'workTeams')
        ], 200);
    }
    
    /**
     * Elimina un usuario específico de la base de datos.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Solo el admin puede eliminar
        if (! Auth::user()->hasRole('admin')) {
             return response()->json(['message' => 'Solo un administrador puede eliminar usuarios.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.'], 200);
    }


    /**
     * Obtiene datos de soporte (roles y equipos) para formularios.
     */
    public function getCreationOptions()
    {
        $assignableRoles = Role::whereIn('name', ['moderator', 'worker'])->select('id', 'name')->get(); 
        $teams = WorkTeam::select('id', 'name')->get();

        return response()->json([
            'roles' => $assignableRoles,
            'teams' => $teams,
        ]);
    }
}