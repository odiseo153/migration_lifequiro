<?php
namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Legacy\Cita;
use App\Models\PatientGroup;
use App\Models\WhereHeMetUs;
use App\Enums\AppointmentType;
use App\Models\Legacy\Paciente;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Facades\DB;

class MigratePatients extends BaseCommand
{
    protected $signature = 'migrate:patients';
    protected $description = 'Migrar datos desde paciente (legacy) hacia patients (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de pacientes...");

        // Cargar datos de referencia una sola vez para mejorar rendimiento
        $whereHeMetUsOptions = WhereHeMetUs::all()->keyBy('id');
        $patientGroups = PatientGroup::all()->keyBy('id');

        // Mapeo de tipos de cita (definido una sola vez fuera del loop)
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

        // Obtener todas las últimas citas de una vez para mejorar rendimiento
        $lastAppointments = Cita::select('paciente_id', 'tipo', 'estado_id', 'hora', 'dia', 'fecha')
            ->where('estado_id', AppointmentStatus::COMPLETADA->value)
            ->whereIn('id', function ($query) {
                $query->select(\DB::raw('MAX(id)'))
                    ->from('cita')
                    ->where('estado_id', AppointmentStatus::COMPLETADA->value)
                    ->groupBy('paciente_id');
            })
            ->get()
            ->keyBy('paciente_id');

        Paciente::
        whereNotIn('id', Patient::pluck('id')->toArray())->
        chunk(500, function ($pacientes) use ($whereHeMetUsOptions, $patientGroups, $CitaTipoOld, $lastAppointments) {
            DB::transaction(function () use ($pacientes, $whereHeMetUsOptions, $patientGroups, $CitaTipoOld, $lastAppointments) {
                $patientsToInsert = [];
                $appointmentsToInsert = [];

                foreach ($pacientes as $p) {
                    $branch_id = $p->centro_id == 0 || $p->centro_id == null ? 1 : $p->centro_id;
                    try {

                        $where_met_us_id = null;
                        $is_refencia_acceptable = $p->referencia != '--' && $p->referencia != '';

                        if ($is_refencia_acceptable) {
                            $referencia = strtolower(trim($p->referencia));
                            $bestScore = 0;
                            $bestMatchId = null;

                            foreach ($whereHeMetUsOptions as $match) {
                                similar_text($referencia, strtolower($match->name), $percent);
                                if ($percent > $bestScore) {
                                    $bestScore = $percent;
                                    $bestMatchId = $match->id;
                                }
                            }

                            if ($bestScore >= 60) {
                                $where_met_us_id = $bestMatchId;
                            }
                        }

                        // Preparar datos del paciente para inserción batch
                        $patientData = [
                            'id' => $p->id,
                            'email' => $p->correo,
                            'identity_document' => $p->cedula_no == '' ? null : $p->cedula_no,
                            'first_name' => $p->nombre ?? "",
                            'last_name' => $p->apellido ?? "sin apellido",
                            'birth_date' => $this->parseDate($p->fecha_nacimiento),
                            'mobile' => $p->celular ?? "",
                            'phone' => $p->telefono ?? "",
                            'token' => rand(1000, 9999),
                            'gender' => $this->mapSexo($p->sexo),
                            'civil_status' => $p->estado_civil,
                            'address' => $p->direccion ?? "",
                            'occupation' => $p->ocupacion ?? "",
                            'comment' => $p->comentario ?? "",
                            'branch_id' => $branch_id,
                            'patient_group_id' => $patientGroups->has($p->grupo) ? $p->grupo : 1,
                            'where_met_us_id' => $where_met_us_id ?? 1,
                            'created_at' => $p->fecha == null ? now() : $this->parseDateInt($p->fecha),
                            'updated_at' => now(),
                        ];

                        $patientsToInsert[] = $patientData;

                        // Si existe última cita para este paciente, preparar datos de cita
                        if (isset($lastAppointments[$p->id])) {
                            $last_appointment_old = $lastAppointments[$p->id];

                            try {
                                $hourFormatted = \Carbon\Carbon::createFromFormat('g:ia', $last_appointment_old->hora)->format('H:i:s');
                            } catch (\Exception $e) {
                                $hourFormatted = '09:00:00'; // Hora por defecto si falla el parsing
                            }

                            $TypeAppointment = $last_appointment_old->tipo > 8 ?
                                AppointmentType::MIP->value :
                                ($CitaTipoOld[$last_appointment_old->tipo] ?? AppointmentType::MIP->value);

                            $appointmentData = [
                                'note' => 'Cita de migración',
                                'patient_id' => $p->id,
                                'branch_id' => $branch_id,
                                'type_of_appointment_id' => $TypeAppointment,
                                'status_id' => $last_appointment_old->estado_id,
                                'date' => $this->parseDateInt($last_appointment_old->dia),
                                'hour' => $hourFormatted,
                                'created_at' => $last_appointment_old->fecha,
                                'updated_at' => now(),
                            ];

                            $appointmentsToInsert[] = $appointmentData;
                        }

                    } catch (\Exception $e) {
                        continue;
                    }
                }

                // Inserción batch de pacientes
                if (!empty($patientsToInsert)) {
                    Patient::upsert($patientsToInsert, ['id'], [
                        'email',
                        'identity_document',
                        'first_name',
                        'last_name',
                        'birth_date',
                        'mobile',
                        'phone',
                        'token',
                        'gender',
                        'civil_status',
                        'address',
                        'occupation',
                        'comment',
                        'branch_id',
                        'patient_group_id',
                        'where_met_us_id',
                        'updated_at'
                    ]);
                    $this->info("Insertados/actualizados " . count($patientsToInsert) . " pacientes");

                    // Solo insertar citas después de que los pacientes se hayan insertado exitosamente
                    if (!empty($appointmentsToInsert)) {
                        // Verificar que todos los pacientes de las citas existen
                        $patientIds = collect($patientsToInsert)->pluck('id')->toArray();
                        $validAppointments = collect($appointmentsToInsert)->filter(function($appointment) use ($patientIds) {
                            return in_array($appointment['patient_id'], $patientIds);
                        })->toArray();

                        if (!empty($validAppointments)) {
                            Appointment::insert($validAppointments);
                            $this->info("Insertadas " . count($validAppointments) . " citas");
                        }

                        $skippedAppointments = count($appointmentsToInsert) - count($validAppointments);
                        if ($skippedAppointments > 0) {
                            $this->warn("Se omitieron {$skippedAppointments} citas por pacientes inexistentes");
                        }
                    }
                } else {
                    // Si no hay pacientes para insertar, verificar que las citas tengan pacientes válidos
                    if (!empty($appointmentsToInsert)) {
                        $patientIds = collect($appointmentsToInsert)->pluck('patient_id')->unique()->toArray();
                        $existingPatients = Patient::whereIn('id', $patientIds)->pluck('id')->toArray();

                        $validAppointments = collect($appointmentsToInsert)->filter(function($appointment) use ($existingPatients) {
                            return in_array($appointment['patient_id'], $existingPatients);
                        })->toArray();

                        if (!empty($validAppointments)) {
                            Appointment::insert($validAppointments);
                            $this->info("Insertadas " . count($validAppointments) . " citas");
                        }

                        $skippedAppointments = count($appointmentsToInsert) - count($validAppointments);
                        if ($skippedAppointments > 0) {
                            $this->warn("Se omitieron {$skippedAppointments} citas por pacientes inexistentes");
                        }
                    }
                }
            });
        });

        $this->info("Migración de pacientes completada.");
    }



    private function mapSexo($sexo)
    {
        return match (strtolower($sexo)) {
            'masculino', 'm' => 'M',
            'femenino', 'f' => 'F',
            default => null,
        };
    }


}
