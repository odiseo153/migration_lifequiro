<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Legacy\Cita;
use App\Enums\AppointmentType;
use Illuminate\Console\Command;

class MigrateCitas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:citas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde citas (legacy) hacia citas (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $CitaTipoOld = [
            1 => AppointmentType::CONSULTA->value,
            2 => AppointmentType::RADIOGRAFIA->value,
            3 => AppointmentType::REPORTE->value,
            4 => AppointmentType::MIP->value,
            5 => AppointmentType::MR->value,
            6 => AppointmentType::COMPARACION->value,
            7 => AppointmentType::MR->value,
            8 => AppointmentType::MIP->value,
        ];

    Cita::
    whereIn('paciente_id', Patient::whereDoesntHave('appointments')->pluck('id')->toArray())
    ->whereIn('centro_id', Branch::pluck('id')->toArray())
    ->whereIn('centro_id', Branch::pluck('id')->toArray())
    ->chunk(500, function ($citas) use ($CitaTipoOld) {
        foreach ($citas as $c) {

            try {
                $hourFormatted = \Carbon\Carbon::createFromFormat('g:ia', $c->hora)->format('H:i:s');
            } catch (\Exception $e) {
                $hourFormatted = '09:00:00'; // Hora por defecto si falla el parsing
            }


            $TypeAppointment = $c->tipo > 8 ?
            AppointmentType::MIP->value :
            ($CitaTipoOld[$c->tipo] ?? AppointmentType::MIP->value);


            $appointmentData = [
                'note' => 'Cita de migración',
                'patient_id' => $c->paciente_id,
                'branch_id' => $c->centro_id,
                'type_of_appointment_id' => $TypeAppointment,
                'status_id' => $c->estado_id,
                'date' => $this->parseDateInt($c->dia),
                'hour' => $hourFormatted,
                'created_at' => $c->fecha,
                'updated_at' => $c->fecha,
            ];

            Appointment::create($appointmentData);
        }
    });

    $this->info("Migración de citas completada.");
    }
}
