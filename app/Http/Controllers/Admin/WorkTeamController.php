<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\WorkTeam;
use App\Models\User;

class WorkTeamController extends Controller
{
    /**
     * Listar todos los equipos visibles según el rol.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'sub_admin'])) {
            $teams = WorkTeam::with(['workSchedules', 'users:id,name,last_name,email'])->get();
        } elseif ($user->hasRole('moderator')) {
            // Moderator ve solo los equipos donde está asignado
            $teams = $user->workTeams()->with(['workSchedules', 'users:id,name,last_name,email'])->get();
        } elseif ($user->hasRole('collaborator')) {
            // Collaborator ve solo su equipo
            $teams = $user->workTeams()->with(['workSchedules', 'users:id,name,last_name,email'])->take(1)->get();
        } else {
            return response()->json(['message' => 'Rol no autorizado.'], 403);
        }

        return response()->json(['teams' => $teams], 200);
    }

    /**
     * Mostrar un equipo específico.
     */
    public function show($id)
    {
        $user = auth()->user();
        $team = WorkTeam::with(['workSchedules', 'users:id,name,last_name,email'])->findOrFail($id);

        if ($user->hasAnyRole(['admin', 'sub_admin'])) {
            // Admin y sub_admin pueden ver cualquier equipo
        } elseif ($user->hasRole('moderator') || $user->hasRole('collaborator')) {
            // Solo puede ver si pertenece al equipo
            if (!$team->users->contains($user->id)) {
                return response()->json(['message' => 'No tienes permiso para ver este equipo.'], 403);
            }
        } else {
            return response()->json(['message' => 'Rol no autorizado.'], 403);
        }

        return response()->json(['team' => $team], 200);
    }

    /**
     * Crear un nuevo equipo y sus horarios base.
     * Solo admin y sub_admin
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'Solo admin o sub_admin pueden crear equipos.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:work_teams,name',
            'schedule' => 'required|array',

            'schedule.lunes.is_working' => 'nullable|in:1',
            'schedule.lunes.start_time' => 'required_if:schedule.lunes.is_working,1|date_format:H:i',
            'schedule.lunes.end_time' => 'required_if:schedule.lunes.is_working,1|date_format:H:i|after:schedule.lunes.start_time',

            'schedule.martes.is_working' => 'nullable|in:1',
            'schedule.martes.start_time' => 'required_if:schedule.martes.is_working,1|date_format:H:i',
            'schedule.martes.end_time' => 'required_if:schedule.martes.is_working,1|date_format:H:i|after:schedule.martes.start_time',

            'schedule.miércoles.is_working' => 'nullable|in:1',
            'schedule.miércoles.start_time' => 'required_if:schedule.miércoles.is_working,1|date_format:H:i',
            'schedule.miércoles.end_time' => 'required_if:schedule.miércoles.is_working,1|date_format:H:i|after:schedule.miércoles.start_time',

            'schedule.jueves.is_working' => 'nullable|in:1',
            'schedule.jueves.start_time' => 'required_if:schedule.jueves.is_working,1|date_format:H:i',
            'schedule.jueves.end_time' => 'required_if:schedule.jueves.is_working,1|date_format:H:i|after:schedule.jueves.start_time',

            'schedule.viernes.is_working' => 'nullable|in:1',
            'schedule.viernes.start_time' => 'required_if:schedule.viernes.is_working,1|date_format:H:i',
            'schedule.viernes.end_time' => 'required_if:schedule.viernes.is_working,1|date_format:H:i|after:schedule.viernes.start_time',

            'schedule.sábado.is_working' => 'nullable|in:1',
            'schedule.sábado.start_time' => 'required_if:schedule.sábado.is_working,1|date_format:H:i',
            'schedule.sábado.end_time' => 'required_if:schedule.sábado.is_working,1|date_format:H:i|after:schedule.sábado.start_time',

            'schedule.domingo.is_working' => 'nullable|in:1',
            'schedule.domingo.start_time' => 'required_if:schedule.domingo.is_working,1|date_format:H:i',
            'schedule.domingo.end_time' => 'required_if:schedule.domingo.is_working,1|date_format:H:i|after:schedule.domingo.start_time',
        ]);

        try {
            DB::beginTransaction();
            $team = WorkTeam::create(['name' => $validatedData['name']]);
            $createdSchedules = $this->syncWorkSchedules($team, $validatedData['schedule']);
            DB::commit();

            return response()->json([
                'message' => 'Equipo de trabajo y horarios base creados.',
                'team' => $team,
                'schedules_created' => $createdSchedules,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear equipo.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar equipo y horarios.
     * Solo admin y sub_admin
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'Solo admin o sub_admin pueden actualizar equipos.'], 403);
        }

        $team = WorkTeam::findOrFail($id);

        $validatedData = $request->validate([
            'name' => [
                'sometimes', 
                'required', 
                'string', 
                'max:255', 
                Rule::unique('work_teams', 'name')->ignore($team->id),
            ],
            'schedule' => 'sometimes|array',
            'schedule.*.is_working' => 'nullable|in:1',
            'schedule.*.start_time' => 'required_if:schedule.*.is_working,1|nullable|date_format:H:i',
            'schedule.*.end_time' => 'required_if:schedule.*.is_working,1|nullable|date_format:H:i|after:schedule.*.start_time',
        ]);

        try {
            DB::beginTransaction();

            if (isset($validatedData['name'])) {
                $team->update(['name' => $validatedData['name']]);
            }

            $updatedSchedules = [];
            if (isset($validatedData['schedule'])) {
                $updatedSchedules = $this->syncWorkSchedules($team, $validatedData['schedule']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Equipo y horarios actualizados.',
                'team' => $team->load('workSchedules'),
                'schedules_updated' => $updatedSchedules,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar equipo.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar equipo.
     * Solo admin y sub_admin
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'Solo admin o sub_admin pueden eliminar equipos.'], 403);
        }

        $team = WorkTeam::findOrFail($id);

        try {
            DB::beginTransaction();
            $team->users()->sync([]);
            $team->workSchedules()->delete();
            $team->delete();
            DB::commit();

            return response()->json(['message' => 'Equipo eliminado correctamente.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar equipo.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sincroniza horarios base del equipo (crea/actualiza).
     */
    private function syncWorkSchedules(WorkTeam $team, array $scheduleData): array
    {
        $team->workSchedules()->delete();
        $createdSchedules = [];
        $daysOfWeek = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'];

        foreach ($daysOfWeek as $day) {
            if (isset($scheduleData[$day]['is_working']) && $scheduleData[$day]['is_working'] == 1) {
                $hours = $scheduleData[$day];
                $schedule = $team->workSchedules()->create([
                    'name' => "Horario {$team->name} - " . ucfirst($day),
                    'start_time' => $hours['start_time'] . ':00',
                    'end_time' => $hours['end_time'] . ':00',
                ]);

                $createdSchedules[] = [
                    'day' => ucfirst($day),
                    'schedule' => $schedule,
                ];
            }
        }

        return $createdSchedules;
    }
}
