<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\CallHistory;
use App\Models\Legacy\Cita;
use App\Enums\AppointmentStatus;
use App\Models\User;


class MigrateHistorialLlamadas extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:historial-llamadas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde historial llamadas (legacy) hacia historial llamadas (nuevo)';

    /**
     * Execute the console command.
     */

    //agregar al historial
    public function handle()
    {
        $this->info("Iniciando migración de historial llamadas...");

        // Obtener IDs de citas que ya tienen CallHistory
        $appointmentIdsWithCallHistory = CallHistory::pluck('appointment_id')->toArray();

        // Obtener IDs de pacientes cuyas citas no tienen CallHistory
        $patientIdsWithoutCallHistory = Patient::whereHas('appointments', function ($query) use ($appointmentIdsWithCallHistory) {
            $query->whereNotIn('id', $appointmentIdsWithCallHistory);
        })->pluck('id')->toArray();

        Cita::
        where('nota_cita', '!=', '')
        ->whereIn('usuario_id',User::pluck('id')->toArray())
        ->whereIn('paciente_id', $patientIdsWithoutCallHistory)
            ->whereIn('estado_id', [AppointmentStatus::POSPUESTA->value, AppointmentStatus::NO_ASISTIO->value, AppointmentStatus::REPROGRAMADA->value, AppointmentStatus::DESACTIVADA->value])
            ->chunk(500, function ($llamadas) {
                foreach ($llamadas as $llamada) {
                    $patient = Patient::find($llamada->paciente_id);

                    $appointment = $patient->appointments()->first();

                    if (!$appointment) {
                        $this->warn("Cita no encontrada - ID: {$llamada->cita_id}. Omitiendo registro.");
                        continue;
                    }

                    if ($appointment->call_histories()->exists()) {
                        $this->warn("La cita ya tiene historial.");
                        continue;
                    }

                    CallHistory::create([
                        'appointment_id' => $appointment->id,
                        'user_id' => $llamada->usuario_id,
                        'note' => $llamada->nota_cita,
                        'old_status' => $llamada->estado_id,
                        'new_status' => $appointment->status_id,
                    ]);
                }
            });
    }
}
