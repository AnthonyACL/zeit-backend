<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\WorkTeam;
use App\Models\WorkSchedule;
use App\Models\UserWorkDay;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Listado general de usuarios (solo admin y sub_admin)
     */
    public function index()
    {
        $auth = Auth::user();

        if (! $auth->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'No tienes acceso a esta lista.'], 403);
        }

        if ($auth->hasRole('admin')) {
            $users = User::with(['roles:name','workTeams','workSchedules'])
                ->where('id', '!=', $auth->id)
                ->get();
        } else {
            // SUB_ADMIN → no ve admins
            $users = User::with(['roles:name','workTeams','workSchedules'])
                ->where('id', '!=', $auth->id)
                ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
                ->get();
        }

        return response()->json([
            'message' => 'Lista de usuarios obtenida correctamente.',
            'users' => $users
        ], 200);
    }

    /**
     * Regenerar horario del usuario al cambiar de equipo
     */
    private function regenerateUserSchedule(User $user, $teamId)
    {
        UserWorkDay::where('user_id', $user->id)->delete();

        $teamSchedule = WorkSchedule::where('work_team_id', $teamId)->get();

        foreach ($teamSchedule as $day) {
            UserWorkDay::create([
                'user_id' => $user->id,
                'day' => $day->day,
                'start_time' => $day->start_time,
                'end_time' => $day->end_time,
            ]);
        }
    }

    /**
     * Crear usuario
     */
    public function store(Request $request)
    {
        $auth = Auth::user();

        // Roles permitidos
        $allowedRoles = $auth->hasRole('admin')
            ? ['sub_admin', 'moderator', 'collaborator']
            : ['moderator', 'collaborator'];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in($allowedRoles)],
            'team_id' => 'nullable|exists:work_teams,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        // Si no tiene equipo → terminar
        if (!$request->filled('team_id')) {
            return response()->json([
                'message' => 'Usuario creado sin equipo ni horario.',
                'user' => $user
            ], 201);
        }

        // Asignar equipo
        $user->workTeams()->sync([$validated['team_id']]);

        // Crear horario
        $this->regenerateUserSchedule($user, $validated['team_id']);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'user' => $user->load('roles','workTeams','workSchedules')
        ], 201);
    }

    /**
     * Mostrar usuario
     */
    public function show($id)
    {
        $auth = Auth::user();

        $user = User::with('roles', 'workTeams', 'workSchedules')->findOrFail($id);

        // Sub_admin NO puede ver administradores
        if ($auth->hasRole('sub_admin') && $user->hasRole('admin')) {
            return response()->json(['message' => 'No puedes ver a un administrador.'], 403);
        }

        return response()->json(['user' => $user], 200);
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, User $user)
    {
        $auth = Auth::user();

        $allowedRoles = $auth->hasRole('admin')
            ? ['sub_admin', 'moderator', 'collaborator']
            : ['moderator', 'collaborator'];

        if ($auth->hasRole('sub_admin') && $user->hasAnyRole(['admin','sub_admin'])) {
            return response()->json(['message' => 'No puedes editar este usuario.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','email',Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in($allowedRoles)],
            'team_id' => 'nullable|exists:work_teams,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles([$validated['role']]);

        $oldTeamId = optional($user->workTeams->first())->id;
        $newTeamId = $validated['team_id'] ?? null;

        if (!$newTeamId) {
            $user->workTeams()->detach();
            return response()->json(['message' => 'Usuario actualizado sin equipo.'], 200);
        }

        // Asignar equipo
        $user->workTeams()->sync([$newTeamId]);

        if ($oldTeamId != $newTeamId) {
            $this->regenerateUserSchedule($user, $newTeamId);
        }

        return response()->json([
            'message' => 'Usuario actualizado.',
            'user' => $user->load('roles','workTeams','workSchedules')
        ], 200);
    }

    /**
     * Eliminar usuario
     */
    public function destroy(User $user)
    {
        $auth = Auth::user();

        if (!$auth->hasAnyRole(['admin','sub_admin'])) {
            return response()->json(['message' => 'No tienes permiso.'], 403);
        }

        if ($auth->id === $user->id) {
            return response()->json(['message' => 'No puedes eliminarte a ti mismo.'], 403);
        }

        if ($auth->hasRole('sub_admin') && $user->hasAnyRole(['admin','sub_admin'])) {
            return response()->json(['message' => 'No puedes eliminar este usuario.'], 403);
        }

        if ($auth->hasRole('admin') && $user->hasRole('admin')) {
            return response()->json(['message' => 'No puedes eliminar otro admin.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado.'], 200);
    }

    /**
     * Obtener roles y equipos disponibles
     */
    public function getCreationOptions()
    {
        $auth = Auth::user();

        $assignable = $auth->hasRole('admin')
            ? ['sub_admin', 'moderator', 'collaborator']
            : ['moderator', 'collaborator'];

        return response()->json([
            'roles' => Role::whereIn('name', $assignable)->get(),
            'teams' => WorkTeam::select('id','name')->get(),
        ], 200);
    }
}
