<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Legacy\Ajuste;
use App\Enums\AppointmentType;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Facades\DB;

class MigrateMIPPatients extends BaseCommand
{
    protected $signature = 'migrate:mip-patients';
    protected $description = 'Migrar datos desde mip (legacy) hacia mip (nuevo)';
    protected $plans = [
        461,
        462,
        458,
        434,
        435,
        436,
        437,
        438,
        439,
        441,
        442,
        443,
        444,
        445,
        446,
        453,
        454,
        455,
        456,
        412,
        416,
        417,
        419,
        420,
        422,
        423,
        426,
        428,
        395,
        396,
        397,
        398,
        400,
        401,
        402,
        404,
        406,
        407,
        399,
        355,
        354,
        353,
        352,
        351,
        350,
        349,
        347,
        346,
        344,
        343,
        341,
        337,
        336,
        335,
        329,
        328,
        327,
        326,
        325,
        324,
        323,
        322,
        314,
        313,
        311,
        309,
        308,
        299,
        287,
        286,
        285,
        283,
        278,
        277,
        276,
        275,
        274,
        273,
        268,
        267,
        266,
        265,
        264,
        263,
        262,
        261,
        258,
        257,
        256,
        255,
        254,
        253,
        252,
        251,
        250,
        249,
        248,
        247,
        246,
        244,
        243,
        242,
        241,
        240
    ];

    public function handle()
    {
        $this->info("Iniciando migración de pacientes mip...");
        $count = 0;

        DB::transaction(function () {
            Ajuste::whereIn('plan_id', $this->plans)
                ->chunk(500, function ($ajustes) use (&$count) {
                    foreach ($ajustes as $ajuste) {
                        $patient = Patient::find($ajuste->paciente_id);
                        if (!$patient) {
                            $this->warn("Paciente no encontrado - ID: {$ajuste->paciente_id}. Omitiendo registro.");
                            $count++;
                            continue;
                        }

                        if ($patient->appointments()->where('type_of_appointment_id', AppointmentType::MIP->value)->exists()) {
                            $count++;

                            continue;
                        }

                        $branch = Branch::find($patient->branch_id);
                        $schedule = $branch?->schedules()->where('available', true)->select('day', 'hour')->first();

                        if (!$schedule) {
                            // Si no hay horario disponible, buscar uno cualquiera y ponerlo disponible
                            $anySchedule = $branch?->schedules()->select('day', 'hour')->first();
                            if ($anySchedule) {
                                $anySchedule->available = true;
                                $anySchedule->save();
                                $schedule = $anySchedule;
                            } else {
                                $branch->schedules()->create([
                                    'day' => now()->dayOfWeek,
                                    'hour' => now()->format('H:i:s'),
                                    'available' => true,
                                ]);
                                $schedule = $branch?->schedules()->where('available', true)->select('day', 'hour')->first();
                            }
                        }

                        Appointment::create([
                            'note' => 'Cita de migración MIP para pacientes de planes excluidos',
                            'patient_id' => $patient->id,
                            'branch_id' => $patient->branch_id,
                            'type_of_appointment_id' => AppointmentType::MIP->value,
                            'status_id' => AppointmentStatus::COMPLETADA->value,
                            'date' => now()->next($schedule->day)->format('Y-m-d'),
                            'hour' => $schedule->hour,
                        ]);
                    }
                });
        });

        $this->info("Migración de pacientes mip completada. Se crearon saltaron {$count} citas.");
    }
}
