<?php
namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Ars;
use App\Models\Invoice;
use App\Models\Patient;
use App\Enums\PlanStatus;
use App\Models\Appointment;
use App\Models\Legacy\Cita;
use App\Models\AssignedPlan;
use App\Models\PatientGroup;
use App\Models\WhereHeMetUs;
use App\Enums\AppointmentType;
use App\Enums\TransactionType;
use App\Models\Legacy\Factura;
use App\Models\Legacy\Paciente;
use App\Models\PlanTransaction;

class MigratePatients extends BaseCommand
{
    protected $signature = 'migrate:patients';
    protected $description = 'Migrar datos desde paciente (legacy) hacia patients (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de pacientes...");

        Paciente::chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                try {

                    $where_met_us_id = null;
                    $is_refencia_acceptable = $p->referencia != '--' && $p->referencia != '';

                    if ($is_refencia_acceptable) {
                        $referencia = strtolower(trim($p->referencia));

                        $matches = WhereHeMetUs::all(); // todos los registros posibles
                        $bestScore = 0;
                        $bestMatchId = null;

                        foreach ($matches as $match) {
                            similar_text($referencia, strtolower($match->name), $percent);

                            if ($percent > $bestScore) {
                                $bestScore = $percent;
                                $bestMatchId = $match->id;
                            }
                        }

                        // Si encontramos algo con similitud aceptable (ej. más del 60%)
                        if ($bestScore >= 60) {
                            $where_met_us_id = $bestMatchId;
                        }
                    }


                    $patient = Patient::updateOrCreate(
                        [
                            'id' => $p->id,
                        ],
                        [
                            'id' => $p->id,
                            'email' => $p->correo,
                            'identity_document' => $p->cedula_no == '' ? null : $p->cedula_no,
                            'first_name' => $p->nombre ?? "",
                            'last_name' => $p->apellido ?? "sin apellido",
                            'birth_date' => $this->parseDate($p->fecha_nacimiento) ? $this->parseDate($p->fecha_nacimiento) : now(),
                            'mobile' => $p->celular ?? "",
                            'phone' => $p->telefono ?? "",
                            'token' => rand(1000, 9999),
                            'gender' => $this->mapSexo($p->sexo),
                            'civil_status' => $p->estado_civil,
                            'address' => $p->direccion ?? "",
                            'occupation' => $p->ocupacion ?? "",
                            'comment' => $p->comentario ?? "",
                            'branch_id' => $p->centro_id == 0 || $p->centro_id == null ? 1 : $p->centro_id, // por defecto
                            'patient_group_id' => PatientGroup::find($p->grupo)?->id ?? 1,
                            'where_met_us_id' => WhereHeMetUs::find($where_met_us_id)?->id ?? 1,
                            'created_at' => $p->fecha == null ? now() : $this->parseDateInt($p->fecha),
                        ]
                    );

                $CitaTipoOld=[
                    1=>AppointmentType::CONSULTA->value,
                    2=>AppointmentType::RADIOGRAFIA->value,
                    3=>AppointmentType::REPORTE->value,
                    4=>AppointmentType::MIP->value,
                    5=>AppointmentType::MR->value,
                    6=>AppointmentType::COMPARACION->value,
                    7=>AppointmentType::MR->value,
                ];


                $last_appointment_old=Cita::
                where('estado_id',AppointmentStatus::COMPLETADA->value)
                ->where('paciente_id',$p->id)->orderBy('id','desc')->first();

if($last_appointment_old){
                $hourFormatted = \Carbon\Carbon::createFromFormat('g:ia', $last_appointment_old->hora)->format('H:i:s');
//$TypeAppointment=$last_appointment_old->tipo > 7 ?  AppointmentType::CONSULTA->value :$CitaTipoOld[$last_appointment_old->tipo];

                Appointment::create([
                'note' => 'Cita de migracion',
                'patient_id' => $p->id,
                'branch_id' => $p->centro_id == 0 || $p->centro_id == null ? 1 : $p->centro_id,
                'type_of_appointment_id' => $CitaTipoOld[$last_appointment_old->tipo],
                'status_id' => $last_appointment_old->estado_id,
                'date' => $this->parseDateInt($last_appointment_old->dia),
                    'hour' => $hourFormatted,
                    'created_at' => $last_appointment_old->fecha,
                ]);
            }

                    /*
                    $ars = Ars::find($p->grupo);
                    if ($ars) {
                        // $documentacion=Documentacion::where('paciente_id',$p->id)->first();

                        $patient->insurance()->create([
                            'is_active' => true,
                            'no_afiliado' => $p->no_afiliado,
                            // 'image_insurance' => $documentacion->file,
                            'ars_id' => $ars->id,
                        ]);
                    }

                    $assigned_plan = AssignedPlan::
                        whereNotIn('status', [PlanStatus::Desactivado->value, PlanStatus::Expirado->value])
                        ->where('patient_id', $p->id)->first();

                    if ($assigned_plan) {
                        $factura = Factura::where('paciente_id', $p->id)->where('ajuste_plan_id', $assigned_plan->id);

                        Invoice::create([
                            'patient_id' => $p->id,
                            'branch_id' => $p->centro_id == 0 ? 1 : $p->centro_id,
                            'payment_method_id' => \App\Enums\PaymentMethodType::EFECTIVO->value,
                            'transaction_type_id' => TransactionType::PAGO_PLAN->value,
                            'type_of_tax_receipt_id' => 1,
                            'no_invoice' => $this->generateRandomCode(Invoice::class, 8, 'no_invoice'),
                            'invoice_token' => 'GENERATED-' . $this->generateRandomCode(Invoice::class, 8, 'invoice_token'),
                            'total' => $factura->sum('monto'),
                            'note' => 'Abono a plan a traves de migracion de paciente id: ' . $p->id,
                        ]);

                        PlanTransaction::create([
                            'assigned_plan_id' => $assigned_plan->id,
                            'patient_id' => $p->id,
                            'amount' => $factura->sum('monto'),
                            'transaction_type' => TransactionType::PAGO_PLAN->value,
                            'description' => 'Plan asignado',
                        ]);

                    }
                    */

                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062) { // Duplicate entry error
                        $this->warn("Paciente duplicado encontrado - ID: {$p->id}, Cédula: {$p->cedula_no}");
                        continue;
                    }
                    throw $e;
                }
            }
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
