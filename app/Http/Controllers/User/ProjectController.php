<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\WorkTeam;

class ProjectController extends Controller
{
    // Listar todos los proyectos
    public function index()
    {
        $projects = Project::with('workTeam')->get();
        return response()->json($projects);
    }

    // Crear un proyecto nuevo
    public function store(Request $request)
    {
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

    // Mostrar un proyecto con sus tareas
    public function show(Project $project)
    {
        $project->load('tasks', 'workTeam');
        return response()->json($project);
    }

    // Actualizar un proyecto
    public function update(Request $request, Project $project)
    {
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
        $project->delete();

        return response()->json(['message' => 'Proyecto eliminado correctamente.']);
    }
}
