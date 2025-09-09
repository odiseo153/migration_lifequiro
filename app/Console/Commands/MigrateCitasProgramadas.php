<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Enums\PlanStatus;
use App\Models\AssignedPlan;
use App\Models\ProgrammingHistory;
use App\Models\Legacy\CitasProgramadas;

class MigrateCitasProgramadas extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:citas-programadas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de citas programadas...");

        //por cada dia hay una hora, quiero que crees una entidad por cada dia y le pongas su hora por ejemplo los dias vienen asi :"l,i"

        CitasProgramadas::chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                $assignedPlan = AssignedPlan::whereNotIn('status', [PlanStatus::Desactivado->value, PlanStatus::Expirado->value,PlanStatus::Completado->value])->find($p->ajuste_plan_id);
                $patient = Patient::find($p->paciente_id);
                if (
                    !$assignedPlan && $p->dias != '' && $p->horas != '' && $patient==null
                ) {
                    $this->warn("Plan no encontrado - ID: {$p->ajuste_plan_id}. Plan desactivado o expirado. Omitiendo registro.");
                    continue;
                }

                // Separar días y horas por comas
                $days = explode(',', trim($p->dias, ','));
                $hours = explode(',', trim($p->horas, ','));

                $dayMapping = [
                    'l' => 'Monday',
                    'm' => 'Tuesday',
                    'i' => 'Wednesday',
                    'j' => 'Thursday',
                    'v' => 'Friday',
                    's' => 'Saturday',
                    'd' => 'Sunday',
                ];

                // Crear un ProgrammingHistory por cada día
                foreach ($days as $index => $day) {
                    if (empty(trim($day))) continue; // Saltar días vacíos

                    $dayName = $dayMapping[trim($day)] ?? null;
                    $hour = isset($hours[$index]) ? trim($hours[$index]) : null;

                    if ($dayName && $hour && $assignedPlan) {
                        // Convertir hora de formato AM/PM a 24 horas
                        $hourFormatted = \Carbon\Carbon::createFromFormat('g:ia', $hour)->format('H:i:s');

                        ProgrammingHistory::updateOrCreate([
                            'id' => $p->id,
                        ], [
                            'id' => $p->id,
                            'branch_id' => $assignedPlan->branch_id,
                            'patient_id' => $p->paciente_id,
                            'assigned_plan_id' => $p->ajuste_plan_id,
                            'day' => $dayName,
                            'hour' => $hourFormatted,
                            'activation_date' => $this->parseDate($p->fecha_activacion),
                            'is_active' => $p->estado == 1 ? true : false,
                            'created_at' => $this->parseDate($p->fecha),
                        ]);
                    }
                }
            }
        });

        /*
        Estado
        1=
        2=si se desactivaron y le pusieron una fecha de activacion
        */

        $this->info("Migración completada de citas programadas.");

    }
}
