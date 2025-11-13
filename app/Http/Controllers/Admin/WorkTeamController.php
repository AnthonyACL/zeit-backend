<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkTeam;
use Illuminate\Support\Facades\DB;
use App\Models\WorkSchedule;
use App\Models\User;
use App\Models\UserWorkDay;

class WorkTeamController extends Controller
{
    public function index()
    {
        // Carga los equipos con sus horarios base y la lista de usuarios asociados
        $teams = WorkTeam::with([
            'workSchedules', 
            // Se asumen las relaciones correctas en el modelo WorkTeam
            'users:id,name,last_name,email' 
        ])->get();

        return response()->json([
            'message' => 'Lista de equipos de trabajo obtenida correctamente.',
            'teams' => $teams
        ], 200);
    }

    /**
     * Muestra los datos detallados de un equipo específico.
     * (Accessible por admin, sub_admin, moderator)
     */
    public function show($id)
    {
        $team = WorkTeam::with(['workSchedules', 'users:id,name,last_name,email'])->findOrFail($id);

        return response()->json(['team' => $team], 200);
    }
    
    /**
     * Almacena un nuevo equipo de trabajo y sus horarios base.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:work_teams,name',
            'schedule' => 'required|array',
            'schedule.*.is_working' => 'nullable|in:1',
            'schedule.*.start_time' => 'required_if:schedule.*.is_working,1|nullable|date_format:H:i',
            'schedule.*.end_time' => 'required_if:schedule.*.is_working,1|nullable|date_format:H:i|after:schedule.*.start_time',
        ]);

        try {
            DB::beginTransaction();

            $team = WorkTeam::create([
                'name' => $validatedData['name'],
            ]);

            $createdSchedules = $this->syncWorkSchedules($team, $validatedData['schedule']);

            DB::commit();

            return response()->json([
                'message' => 'Grupo de trabajo y horarios base creados con éxito.',
                'team' => $team,
                'schedules_created' => $createdSchedules,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocurrió un error al crear el grupo de trabajo y sus horarios.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Actualiza un equipo de trabajo y sus horarios base.
     * (Accessible por admin, sub_admin, moderator)
     */
    public function update(Request $request, $id)
    {
        $team = WorkTeam::findOrFail($id);

        $validatedData = $request->validate([
            'name' => [
                'sometimes', 
                'required', 
                'string', 
                'max:255', 
                // Asegura que el nombre sea único, ignorando el ID actual
                Rule::unique('work_teams', 'name')->ignore($team->id),
            ],
            // 'schedule' es opcional en la actualización, pero si se envía, debe ser un array válido
            'schedule' => 'sometimes|array',
            'schedule.*.is_working' => 'nullable|in:1',
            'schedule.*.start_time' => 'required_if:schedule.*.is_working,1|nullable|date_format:H:i',
            'schedule.*.end_time' => 'required_if:schedule.*.is_working,1|nullable|date_format:H:i|after:schedule.*.start_time',
        ]);

        try {
            DB::beginTransaction();

            // 1. Actualiza el nombre del equipo si se proporciona
            if (isset($validatedData['name'])) {
                $team->update(['name' => $validatedData['name']]);
            }
            
            $updatedSchedules = [];

            // 2. Actualiza/Sincroniza los horarios si se proporcionan
            if (isset($validatedData['schedule'])) {
                $updatedSchedules = $this->syncWorkSchedules($team, $validatedData['schedule']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Grupo de trabajo y horarios base actualizados con éxito.',
                'team' => $team->load('workSchedules'),
                'schedules_updated' => $updatedSchedules,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocurrió un error al actualizar el grupo de trabajo y sus horarios.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Elimina un equipo de trabajo y desasocia a todos los usuarios.
     * (Solo permitido para admin y sub_admin)
     */
    public function destroy($id)
    {
        // Verificar permisos específicos para la eliminación (Control de roles de Spatie)
        if (! Auth::user()->hasAnyRole(['admin', 'sub_admin'])) {
            return response()->json(['message' => 'Solo un administrador o sub-administrador puede eliminar equipos.'], 403);
        }
        
        $team = WorkTeam::findOrFail($id);

        try {
            DB::beginTransaction();

            // Desasociar a todos los usuarios de este equipo.
            $team->users()->sync([]); 
            
            // Eliminar los horarios base asociados al equipo
            $team->workSchedules()->delete();

            // Eliminar el equipo
            $team->delete();
            
            DB::commit();

            return response()->json(['message' => 'Equipo de trabajo eliminado correctamente.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocurrió un error al intentar eliminar el equipo de trabajo.',
                'error' => $e->getMessage(),
            ], 500); 
        }
    }
    
    /**
     * Lógica compartida para eliminar y recrear los horarios base del equipo.
     * Esto simplifica la actualización.
     *
     * @param WorkTeam $team
     * @param array $scheduleData
     * @return array
     */
    private function syncWorkSchedules(WorkTeam $team, array $scheduleData): array
    {
        // 1. Eliminar todos los horarios base existentes para el equipo.
        $team->workSchedules()->delete();

        $createdSchedules = [];
        $daysOfWeek = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

        // 2. Recrear los horarios base
        foreach ($daysOfWeek as $day) {
            if (isset($scheduleData[$day]['is_working']) && $scheduleData[$day]['is_working'] == 1) {
                
                $hours = $scheduleData[$day];
                
                $schedule = $team->workSchedules()->create([
                    'name' => "Horario Base {$team->name} - " . ucfirst($day),
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

