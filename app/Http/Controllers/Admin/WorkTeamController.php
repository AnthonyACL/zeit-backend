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

            // Crea el Equipo de Trabajo (WorkTeam)
            $team = WorkTeam::create([
                'name' => $validatedData['name'],
            ]);

            $createdSchedules = [];
            $daysOfWeek = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

            //Crea los Horarios Base asociados al equipo
            foreach ($daysOfWeek as $day) {
                if (isset($validatedData['schedule'][$day]['is_working']) && $validatedData['schedule'][$day]['is_working'] == 1) {
                    
                    $hours = $validatedData['schedule'][$day];
                    
                    // Crea el horario específico para el equipo en ese día.
                    $schedule = $team->workSchedules()->create([
                        'name' => "Horario Base {$team->name} - " . ucfirst($day),
                        'start_time' => $hours['start_time'] . ':00', 
                        'end_time' => $hours['end_time'] . ':00',    
                    ]);

                    // Almacenamos el schedule creado
                    $createdSchedules[] = [
                        'day' => ucfirst($day),
                        'schedule' => $schedule,
                    ];
                    
                }
            }

            DB::commit();

            // Respuesta de éxito (JSON)
            return response()->json([
                'message' => 'Grupo de trabajo y horarios base creados con éxito.',
                'team' => $team,
                'schedules_created' => $createdSchedules,
            ], 201); // Código 201 creado

        } catch (\Exception $e) {
            DB::rollBack();

            // Respuesta de error (JSON)
            return response()->json([
                'message' => 'Ocurrió un error al crear el grupo de trabajo y sus horarios.',
                'error' => $e->getMessage(),
            ], 500); // Código 500: Internal Server Error
        }
    }
}

