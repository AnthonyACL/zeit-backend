<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\WorkTeam;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    // Listar todos los proyectos visibles según rol
    public function index()
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['admin', 'sub_admin'])) {
            $projects = Project::with('workTeam')->get();
        } elseif ($user->hasRole('moderator')) {
            // Moderator ve los proyectos de los equipos en los que está asignado
            $teamIds = $user->workTeams()->pluck('id');
            $projects = Project::with('workTeam')
                               ->whereIn('work_team_id', $teamIds)
                               ->get();
        } elseif ($user->hasRole('collaborator')) {
            // Collaborator ve solo los proyectos de su equipo
            $team = $user->workTeams()->first();
            $projects = $team ? Project::with('workTeam')
                                        ->where('work_team_id', $team->id)
                                        ->get() 
                              : collect();
        } else {
            return response()->json(['message' => 'Rol no autorizado.'], 403);
        }

        return response()->json($projects);
    }

    // Crear un proyecto nuevo
    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'No tienes permiso para crear proyectos.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'recursos' => 'nullable|string',
            'status' => 'in:pendiente,en_progreso,finalizado',
            'work_team_id' => 'nullable|exists:work_teams,id',
        ]);

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Proyecto creado correctamente.',
            'project' => $project,
        ], 201);
    }

    // Mostrar un proyecto específico
    public function show(Project $project)
    {
        $user = Auth::user();

        // Admin y sub_admin pueden ver cualquier proyecto
        if ($user->hasAnyRole(['admin', 'sub_admin'])) {
            $project->load('tasks', 'workTeam');
        } elseif ($user->hasRole('moderator') || $user->hasRole('collaborator')) {
            // Solo puede ver si pertenece al equipo del proyecto
            if (!$user->workTeams->contains($project->work_team_id)) {
                return response()->json(['message' => 'No tienes permiso para ver este proyecto.'], 403);
            }
            $project->load('tasks', 'workTeam');
        } else {
            return response()->json(['message' => 'Rol no autorizado.'], 403);
        }

        return response()->json($project);
    }

    // Actualizar un proyecto
    public function update(Request $request, Project $project)
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'No tienes permiso para actualizar proyectos.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'recursos' => 'nullable|string',
            'status' => 'in:pendiente,en_progreso,finalizado',
            'work_team_id' => 'nullable|exists:work_teams,id',
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Proyecto actualizado correctamente.',
            'project' => $project,
        ]);
    }

    // Eliminar un proyecto
    public function destroy(Project $project)
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'No tienes permiso para eliminar proyectos.'], 403);
        }

        $project->delete();

        return response()->json(['message' => 'Proyecto eliminado correctamente.']);
    }
}
