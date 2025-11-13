<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Project;

class TaskController extends Controller
{
    // Listar tareas de un proyecto específico
    public function index(Project $project)
    {
        $tasks = $project->tasks()->with('assignee')->get();
        return response()->json($tasks);
    }

    // Crear una tarea dentro de un proyecto
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'in:baja,media,alta',
            'status' => 'in:por_hacer,en_progreso,en_revision,completado',
            'order' => 'integer|min:0',
        ]);

        $task = $project->tasks()->create($validated);

        return response()->json([
            'message' => 'Tarea creada correctamente.',
            'task' => $task,
        ], 201);
    }

    // Actualizar una tarea
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'in:baja,media,alta',
            'status' => 'in:por_hacer,en_progreso,en_revision,completado',
            'order' => 'integer|min:0',
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Tarea actualizada correctamente.',
            'task' => $task,
        ]);
    }

    // Eliminar una tarea
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->json(['message' => 'Tarea eliminada correctamente.']);
    }
}
