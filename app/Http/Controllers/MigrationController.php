<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Item;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Enums\ItemType;
use App\Models\Patient;
use App\Models\Voucher;
use App\Enums\PlanStatus;
use App\Models\CreditNote;
use App\Models\Appointment;
use App\Models\PatientItem;
use App\Models\AssignedPlan;
use App\Models\PatientGroup;
use App\Models\WhereHeMetUs;
use Illuminate\Http\Request;
use App\Enums\ServicesStatus;
use App\Models\Legacy\Ajuste;
use App\Models\Legacy\Compra;
use App\Models\MedicalRecord;
use App\Enums\AppointmentType;
use App\Models\Legacy\Balance;
use App\Models\AcquiredService;
use App\Models\Legacy\Paciente;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethodType;
use App\Enums\AuthorizationStatus;
use App\Models\Legacy\Antecedente;
use App\Models\TypeOfAppointments;
use Illuminate\Support\Facades\DB;
use App\Models\MedicalAjusteModule;
use Illuminate\Support\Facades\Log;
use App\Models\DescuentAuthorization;
use App\Models\Legacy\HistorialAjuste;
use App\Models\Legacy\HistorialTerapia;
use App\Models\PhysicalTherapyCategory;
use App\Models\MedicalTerapiaTracionModule;

class MigrationController extends Controller
{
    protected $ignored_plan = [461, 462, 458, 434, 435, 436, 437, 438, 439, 441, 442, 443, 444, 445, 446, 453, 454, 455, 456, 412, 416, 417, 419, 420, 422, 423, 426, 428, 395, 396, 397, 398, 400, 401, 402, 404, 406, 407, 399, 355, 354, 353, 352, 351, 350, 349, 347, 346, 344, 343, 341, 337, 336, 335, 329, 328, 327, 326, 325, 324, 323, 322, 314, 313, 311, 309, 308, 299, 287, 286, 285, 283, 278, 277, 276, 275, 274, 273, 268, 267, 266, 265, 264, 263, 262, 261, 258, 257, 256, 255, 254, 253, 252, 251, 250, 249, 248, 247, 246, 244, 243, 242, 241, 240];

    public function migratePatientsByIds(Request $request)
    {
        ini_set('memory_limit', '2G'); // Aumentar memoria para optimizaciones
        ini_set('max_execution_time', 600); // Aumentar tiempo límite

        $request->validate([
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'integer'
        ]);

        $patientIds = $request->input('patient_ids');

        try {
            DB::beginTransaction();

            $results = [
                'success' => true,
                'migrated_patients' => 0,
                'migrated_appointments' => 0,
                'migrated_plans' => 0,
                'migrated_balances' => 0,
                'migrated_purchases' => 0,
                'migrated_medical_records' => 0,
                'migrated_adjustments' => 0,
                'migrated_therapies' => 0,
                'errors' => []
            ];

            // 1. OPTIMIZACIÓN: Procesar pacientes en lotes para mejor rendimiento
            $idMapping = [];
            $migratedPatients = [];
            $batchSize = 100; // Procesar de 100 en 100

            $patientBatches = array_chunk($patientIds, $batchSize);

            foreach ($patientBatches as $batchIndex => $batch) {
                Log::info("Procesando lote " . ($batchIndex + 1) . " de " . count($patientBatches) . " (pacientes: " . count($batch) . ")");

                $batchResults = $this->migratePatients($batch, $results, $idMapping);
                $migratedPatients = array_merge($migratedPatients, $batchResults);

                // Liberar memoria entre lotes
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
            if (!empty($migratedPatients)) {
                // 2. Migrar planes asignados
                $this->migratePlanes($patientIds, $results, $idMapping);

                // 3. Migrar balance
                $this->migrateBalance($patientIds, $results, $idMapping);

                // 4. Migrar compras
                $this->migrateCompras($patientIds, $results, $idMapping);

                // 5. Migrar antecedentes
                $this->migrateAntecedentes($patientIds, $results, $idMapping);

                // 6. Migrar historial de ajuste
                $this->migrateHistorialAjuste($patientIds, $results, $idMapping);

                // 7. Migrar historial terapia física
                $this->migrateHistorialTerapiaFisicas($patientIds, $results, $idMapping);
            }

            DB::commit();

            return response()->json($results);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en migración de pacientes: ' . $e->getMessage(), [
                'patient_ids' => $patientIds,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error durante la migración: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    private function migratePatients(array $patientIds, array &$results, array &$idMapping)
    {
        // Cargar datos de referencia
        $whereHeMetUsOptions = WhereHeMetUs::all()->keyBy('id');
        $patientGroups = PatientGroup::all()->keyBy('id');

        // Mapeo de tipos de cita
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

        // OPTIMIZACIÓN: Consulta más eficiente para últimas citas - USANDO CONEXIÓN LEGACY
        $lastAppointments = DB::connection('legacy')->table('cita as c1')
            ->select('c1.paciente_id', 'c1.tipo', 'c1.estado_id', 'c1.hora', 'c1.dia', 'c1.fecha')
            ->leftJoin('cita as c2', function ($join) {
                $join->on('c1.paciente_id', '=', 'c2.paciente_id')
                    ->on('c1.id', '<', 'c2.id')
                    ->where('c2.estado_id', AppointmentStatus::COMPLETADA->value);
            })
            ->where('c1.estado_id', AppointmentStatus::COMPLETADA->value)
            ->whereIn('c1.paciente_id', $patientIds)
            ->whereNull('c2.id')
            ->get()
            ->keyBy('paciente_id');

        // Obtener todos los pacientes legacy solicitados
        $pacientes = Paciente::whereIn('id', $patientIds)->get();

        // OPTIMIZACIÓN: Pre-cargar pacientes existentes para evitar consultas N+1
        $existingPatientIds = Patient::whereIn('id', $patientIds)->pluck('id')->toArray();
        $existingPatientsByName = Patient::select('id', 'first_name', 'last_name')
            ->get()
            ->mapWithKeys(function ($patient) {
                $fullName = strtolower(trim($patient->first_name . ' ' . $patient->last_name));
                return [$fullName => $patient->id];
            });

        // OPTIMIZACIÓN: Pre-procesar referencias para evitar similar_text en bucle
        $whereHeMetUsMap = $this->buildReferenceMap($whereHeMetUsOptions);

        $migratedPatients = [];
        $patientsToInsert = [];
        $appointmentsToInsert = [];

        Log::info("Procesando " . count($pacientes) . " pacientes en este lote");

        foreach ($pacientes as $index => $p) {
            try {
                // Log progreso cada 10 pacientes
                if ($index % 10 == 0) {
                    Log::info("Procesando paciente " . ($index + 1) . " de " . count($pacientes));
                }

                $branch_id = $p->centro_id == 0 || $p->centro_id == null ? 1 : $p->centro_id;

                // OPTIMIZACIÓN: Usar datos pre-cargados para determinar ID final
                $finalPatientId = $this->determineFinalPatientIdOptimized($p, $existingPatientIds, $existingPatientsByName);

                // Si el ID es null, significa que ya existe un paciente con el mismo nombre
                if ($finalPatientId === null) {
                    $results['errors'][] = "Paciente {$p->id} ({$p->nombre} {$p->apellido}) ya existe en la base de datos con el mismo nombre";
                    continue;
                }

                // OPTIMIZACIÓN: Usar mapeo pre-procesado en lugar de similar_text
                $where_met_us_id = null;
                $is_refencia_acceptable = $p->referencia != '--' && $p->referencia != '';

                if ($is_refencia_acceptable) {
                    $referencia = strtolower(trim($p->referencia));
                    $where_met_us_id = $whereHeMetUsMap[$referencia] ?? null;
                }

                // Preparar datos del paciente
                $patientData = [
                    'id' => $finalPatientId,
                    'email' => $p->correo,
                    'identity_document' => $p->cedula_no == '' ? null : $p->cedula_no,
                    'first_name' => $p->nombre ?? 'sin nombre',
                    'last_name' => $p->apellido ?? 'sin apellido',
                    'birth_date' => $this->parseDate($p->fecha_nacimiento),
                    'mobile' => $p->celular ?? '',
                    'phone' => $p->telefono ?? '',
                    'token' => rand(1000, 9999),
                    'gender' => $this->mapSexo($p->sexo),
                    'civil_status' => $p->estado_civil,
                    'address' => $p->direccion ?? '',
                    'occupation' => $p->ocupacion ?? '',
                    'comment' => $p->comentario ?? '',
                    'branch_id' => $branch_id,
                    'patient_group_id' => $patientGroups->has($p->grupo) ? $p->grupo : 1,
                    'where_met_us_id' => $where_met_us_id ?? 1,
                    'created_at' => $p->fecha == null ? now() : $this->parseDateInt($p->fecha),
                    'updated_at' => now(),
                    'old_id' => in_array($p->id, $existingPatientIds) ? $p->id : null,
                ];

                $patientsToInsert[] = $patientData;
                $migratedPatients[] = $finalPatientId;

                // Guardar mapeo de ID original a ID final
                $idMapping[$p->id] = $finalPatientId;

                // Preparar cita si existe
                if (isset($lastAppointments[$p->id])) {
                    $last_appointment_old = $lastAppointments[$p->id];

                    try {
                        $hourFormatted = \Carbon\Carbon::createFromFormat('g:ia', $last_appointment_old->hora)->format('H:i:s');
                    } catch (\Exception $e) {
                        $hourFormatted = '09:00:00';
                    }

                    $TypeAppointment = $last_appointment_old->tipo > 8 ?
                        AppointmentType::MIP->value :
                        ($CitaTipoOld[$last_appointment_old->tipo] ?? AppointmentType::MIP->value);

                    $appointmentData = [
                        'note' => 'Cita de migración',
                        'patient_id' => $finalPatientId,
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
                $results['errors'][] = "Error migrando paciente {$p->id}: " . $e->getMessage();
                continue;
            }
        }

        // OPTIMIZACIÓN: Insertar pacientes en lotes más pequeños para evitar timeouts
        if (!empty($patientsToInsert)) {
            $insertBatchSize = 50; // Lotes más pequeños para inserción
            $patientChunks = array_chunk($patientsToInsert, $insertBatchSize);

            foreach ($patientChunks as $chunk) {
                Patient::upsert($chunk, ['id'], [
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
            }
            $results['migrated_patients'] += count($patientsToInsert);
        }

        // OPTIMIZACIÓN: Insertar citas en lotes
        if (!empty($appointmentsToInsert)) {
            $appointmentPatientIds = collect($appointmentsToInsert)->pluck('patient_id')->unique()->toArray();
            $existingPatientIds = Patient::whereIn('id', $appointmentPatientIds)->pluck('id')->toArray();

            $validAppointments = collect($appointmentsToInsert)->filter(function ($appointment) use ($existingPatientIds) {
                return in_array($appointment['patient_id'], $existingPatientIds);
            })->toArray();

            if (!empty($validAppointments)) {
                $appointmentChunks = array_chunk($validAppointments, $insertBatchSize);
                foreach ($appointmentChunks as $chunk) {
                    Appointment::insert($chunk);
                }
                $results['migrated_appointments'] += count($validAppointments);
            }
        }

        return $migratedPatients;
    }

    private function migratePlanes(array $patientIds, array &$results, array $idMapping)
    {
        $planesAsignados = Ajuste::whereIn('paciente_id', $patientIds)
            ->whereIn('plan_id', Plan::whereNotIn('id', $this->ignored_plan)->pluck('id')->toArray())
            ->whereIn('estado', [1, 2, 3])
            ->get();

        $user = User::first();
        $planStatusMatch = [
            1 => PlanStatus::Activo->value,
            2 => PlanStatus::Expirado->value,
            3 => PlanStatus::Completado->value,
            4 => PlanStatus::Desactivado->value,
        ];

        foreach ($planesAsignados as $p) {
            try {
                if (!Plan::find($p->plan_id)) {
                    $results['errors'][] = "Plan no encontrado - ID: {$p->plan_id}";
                    continue;
                }

                // Usar el ID mapeado si existe, sino usar el original
                $finalPatientId = $idMapping[$p->paciente_id] ?? $p->paciente_id;

                $assignedPlan = AssignedPlan::create([
                    'id' => $p->id,
                    'plan_id' => $p->plan_id,
                    'patient_id' => $finalPatientId,
                    'date_start' => $this->parseDate($p->fecha_ciclo_insertada),
                    'date_end' => $this->parseDate($p->fecha_expiracion),
                    'plan_name' => Plan::find($p->plan_id)->name ?? 'Plan ' . $this->generateRandomCode(AssignedPlan::class, 8, 'plan_name'),
                    'paid_type' => 1,
                    'amount' => $p->costo,
                    'therapies_number' => $p->terapias_fisicas,
                    'number_installments' => Plan::find($p->plan_id)->number_installments ?? 0,
                    'status' => $planStatusMatch[$p->estado],
                    'branch_id' => $p->centro_id,
                    'user_id' => $user->id,
                    'card_commission' => $p->card_fee,
                    'bank_commission' => $p->bank_fee,
                    'other_commission' => $p->other_fee,
                    'created_at' => $this->parseDateInt($p->fecha_cre),
                    'updated_at' => $this->parseDateInt($p->fecha_cre),
                ]);

                // Crear transacción
                $assignedPlan->transactions()->create([
                    'assigned_plan_id' => $assignedPlan->id,
                    'patient_id' => $finalPatientId,
                    'amount' => $p->pagado,
                    'transaction_type' => 'entrada',
                    'description' => 'Plan asignado',
                ]);

                // Crear descuento si existe
                if ($p->descuento != 0) {
                    DescuentAuthorization::create([
                        'patient_id' => $finalPatientId,
                        'assigned_plan_id' => $assignedPlan->id,
                        'type' => 1,
                        'request_amount' => $p->descuento,
                        'approved_amount' => $p->descuento,
                        'status' => 2,
                        'request_by' => $user->id,
                        'authorized_by' => $user->id,
                        'authorized_at' => now(),
                        'created_at' => $this->parseDateInt($p->fecha_cre),
                        'updated_at' => $this->parseDateInt($p->fecha_cre),
                    ]);
                }

                // Crear vouchers y servicios adquiridos
                $this->createVouchersAndServices($assignedPlan, $p, $finalPatientId);

                $results['migrated_plans']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Error migrando plan {$p->id}: " . $e->getMessage();
            }
        }
    }

    private function createVouchersAndServices($assignedPlan, $p, $finalPatientId)
    {
        // Calcular precio por ítem
        $total_items = $assignedPlan->plan->total_sessions + $assignedPlan->therapies_number;
        $item_price = $total_items != 0 ? $assignedPlan->amount / $total_items : 0;

        $total_consumed_items = (int) $p->sesiones_utilizadas + (int) $p->terapias_utilizadas;

        // Crear vouchers
        if ($p->consumido > 0 && $item_price > 0) {
            $vouchers_needed = round($p->consumido / $item_price);
            $vouchers_needed = min($vouchers_needed, $total_consumed_items);

            for ($i = 0; $i < $vouchers_needed; $i++) {
                Voucher::create([
                    'assigned_plan_id' => $assignedPlan->id,
                    'status' => 3,
                    'quantity' => 1,
                    'price' => $item_price,
                    'created_at' => $this->parseDateInt($p->fecha_cre),
                ]);
            }
        } elseif ($p->consumido > 0 && $total_consumed_items > 0) {
            $price_per_voucher = $p->consumido / $total_consumed_items;

            for ($i = 0; $i < $total_consumed_items; $i++) {
                Voucher::create([
                    'assigned_plan_id' => $assignedPlan->id,
                    'status' => 3,
                    'quantity' => 1,
                    'price' => $price_per_voucher,
                    'created_at' => $this->parseDateInt($p->fecha_cre),
                ]);
            }
        }

        // Crear servicios adquiridos para ajustes
        if ($p->sesiones_utilizadas != 0) {
            $itemAjuste = Item::where('plan', true)->where('type_of_item_id', ItemType::AJUSTE->value)->first();
            $sessiones = (int) $p->sesiones_utilizadas;

            for ($i = 0; $i < $sessiones; $i++) {
                AcquiredService::create([
                    'patient_id' => $finalPatientId,
                    'assigned_plan_id' => $assignedPlan->id,
                    'plan_item_id' => $itemAjuste->id,
                    'price' => $item_price,
                    'status' => ServicesStatus::COMPLETADA->value,
                ]);
            }
        }

        // Crear servicios adquiridos para terapias
        if ($p->terapias_utilizadas != 0) {
            $itemTerapia = Item::where('plan', true)->where('type_of_item_id', ItemType::TERAPIA_FISICA->value)->first();
            $terapias = (int) $p->terapias_utilizadas;

            for ($i = 0; $i < $terapias; $i++) {
                AcquiredService::create([
                    'patient_id' => $finalPatientId,
                    'assigned_plan_id' => $assignedPlan->id,
                    'plan_item_id' => $itemTerapia->id,
                    'price' => $item_price,
                    'status' => ServicesStatus::COMPLETADA->value,
                ]);
            }
        }
    }

    private function migrateBalance(array $patientIds, array &$results, array $idMapping)
    {
        $balances = Balance::where('monto', '>', 0)
            ->where('estado', 1)
            ->whereIn('paciente_id', $patientIds)
            ->whereNotIn('id', CreditNote::pluck('id')->toArray())
            ->get();

        foreach ($balances as $balance) {
            try {
                // Usar el ID mapeado si existe, sino usar el original
                $finalPatientId = $idMapping[$balance->paciente_id] ?? $balance->paciente_id;

                if (!Patient::find($finalPatientId)) {
                    $results['errors'][] = "Paciente no encontrado para balance - ID: {$balance->paciente_id} (mapeado: {$finalPatientId})";
                    continue;
                }

                CreditNote::create([
                    'id' => $balance->id,
                    'patient_id' => $finalPatientId,
                    'amount' => $balance->monto,
                    'payment_method_id' => PaymentMethodType::NOTA_CREDITO->value,
                    'note' => "Balance de paciente migrado",
                ]);

                $results['migrated_balances']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Error migrando balance {$balance->id}: " . $e->getMessage();
            }
        }
    }

    private function migrateCompras(array $patientIds, array &$results, array $idMapping)
    {
        $comprasTipo = [
            1 => ItemType::CONSULTA->value,
            2 => ItemType::RADIOGRAFIA->value,
            3 => ItemType::COMPARACION->value,
            4 => ItemType::REPORTE->value,
            5 => ItemType::AJUSTE->value,
            6 => ItemType::AJUSTE->value,
            7 => ItemType::TERAPIA_FISICA->value,
            8 => ItemType::TRACCION->value,
            9 => ItemType::TERAPIA_FISICA->value,
        ];

        $compras = Compra::where('estado', 1)
            ->where('tipo_servicio', '!=', 0)
            ->whereIn('paciente_id', $patientIds)
            ->get();

        foreach ($compras as $compra) {
            try {
                // Usar el ID mapeado si existe, sino usar el original
                $finalPatientId = $idMapping[$compra->paciente_id] ?? $compra->paciente_id;

                if (!Patient::find($finalPatientId)) {
                    $results['errors'][] = "Paciente no encontrado para compra - ID: {$compra->paciente_id} (mapeado: {$finalPatientId})";
                    continue;
                }

                $item = Item::where('type_of_item_id', $comprasTipo[$compra->tipo_servicio])->first();
                if (!$item) {
                    $item = Item::factory()->create([
                        'type_of_item_id' => $comprasTipo[$compra->tipo_servicio],
                    ]);
                }

                PatientItem::updateOrCreate(
                    ['id' => $compra->id],
                    [
                        'id' => $compra->id,
                        'patient_id' => $finalPatientId,
                        'item_id' => $item->id,
                        'description' => $compra->servicio,
                        'quantity' => 1,
                        'total' => $compra->costo,
                        'created_at' => $compra->fecha_comprado,
                    ]
                );

                $results['migrated_purchases']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Error migrando compra {$compra->id}: " . $e->getMessage();
            }
        }
    }

    private function migrateAntecedentes(array $patientIds, array &$results, array $idMapping)
    {
        $antecedentes = Antecedente::whereIn('paciente_id', $patientIds)
            ->whereNotIn('paciente_id', MedicalRecord::pluck('patient_id')->toArray())
            ->get();

        foreach ($antecedentes as $antecedente) {
            try {
                // Usar el ID mapeado si existe, sino usar el original
                $finalPatientId = $idMapping[$antecedente->paciente_id] ?? $antecedente->paciente_id;

                $patient = Patient::find($finalPatientId);
                if (!$patient) {
                    $results['errors'][] = "Paciente no encontrado para antecedente - ID: {$antecedente->paciente_id} (mapeado: {$finalPatientId})";
                    continue;
                }

                MedicalRecord::create([
                    'patient_id' => $patient->id,
                    'id' => $antecedente->id,
                    'consultation_reason' => $antecedente->motivo_consulta,
                    'medical_history' => $antecedente->motivos_visita_medico,
                    'symptoms_impact_on_life' => $antecedente->dano_sintomas,
                    'current_medication' => $antecedente->medicamentos,
                ]);

                $results['migrated_medical_records']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Error migrando antecedente {$antecedente->id}: " . $e->getMessage();
            }
        }
    }

    private function migrateHistorialAjuste(array $patientIds, array &$results, array $idMapping)
    {
        $historiales = HistorialAjuste::whereIn('paciente_id', $patientIds)->get();

        foreach ($historiales as $historial) {
            try {
                // Usar el ID mapeado si existe, sino usar el original
                $finalPatientId = $idMapping[$historial->paciente_id] ?? $historial->paciente_id;

                if (!Patient::find($finalPatientId)) {
                    $results['errors'][] = "Paciente no encontrado para historial ajuste - ID: {$historial->paciente_id} (mapeado: {$finalPatientId})";
                    continue;
                }

                $item = Item::where('type_of_item_id', ItemType::AJUSTE->value)->first();
                if (!$item) {
                    $item = Item::factory()->create([
                        'type_of_item_id' => ItemType::AJUSTE->value,
                    ]);
                }

                $service = PatientItem::create([
                    'id' => $historial->service_id,
                    'patient_id' => $finalPatientId,
                    'item_id' => $item->id,
                    'quantity' => 0,
                    'price' => $item->price,
                    'total' => $item->price,
                    'created_at' => $historial->fecha,
                ]);

                $room = Room::inRandomOrder()->first() ?? Room::factory()->create();
                $bed = Bed::inRandomOrder()->first() ?? Bed::factory()->create();
                $user = User::inRandomOrder()->first() ?? User::factory()->create();

                $service->waiting_room()->create([
                    'patient_id' => $finalPatientId,
                    'room_id' => $room->id,
                    'bed_id' => $bed->id,
                    'user_id' => $user->id,
                    'created_at' => $historial->fecha,
                ]);

                $acquiredService = AcquiredService::create([
                    'patient_id' => $finalPatientId,
                    'price' => $item->price,
                    'status' => ServicesStatus::COMPLETADA->value,
                    'patient_item_id' => $service->id,
                    'created_at' => $historial->fecha,
                ]);

                // Procesar zonas de vertebras
                $this->processVertebrae($historial, $service, $acquiredService, $finalPatientId);

                $results['migrated_adjustments']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Error migrando historial ajuste {$historial->id}: " . $e->getMessage();
            }
        }
    }

    private function processVertebrae($historial, $service, $acquiredService, $finalPatientId)
    {
        $cervicalVertebrae = [];
        $thoracicVertebrae = [];
        $lumbarVertebrae = [];

        if (!empty($historial->zonas)) {
            $zonas = explode(',', $historial->zonas);
            foreach ($zonas as $zona) {
                $zona = trim($zona);

                if (preg_match('/^c\d+/i', $zona)) {
                    $cervicalVertebrae[] = $zona;
                    if (stripos($zona, 'right') !== false) {
                        $cervicalVertebrae[] = 'right';
                    }
                } elseif (preg_match('/^d\d+/i', $zona)) {
                    $thoracicVertebrae[] = $zona;
                    if (stripos($zona, 'right') !== false) {
                        $thoracicVertebrae[] = 'right';
                    }
                } elseif (preg_match('/^l\d+/i', $zona)) {
                    $lumbarVertebrae[] = $zona;
                    if (stripos($zona, 'right') !== false) {
                        $lumbarVertebrae[] = 'right';
                    }
                } elseif (preg_match('/^s/i', $zona)) {
                    $lumbarVertebrae[] = $zona;
                    if (stripos($zona, 'right') !== false) {
                        $lumbarVertebrae[] = 'right';
                    }
                } else {
                    $cervicalVertebrae[] = $zona;
                }
            }
        }

        MedicalAjusteModule::updateOrCreate(
            ['id' => $historial->id],
            [
                'id' => $historial->id,
                'patient_id' => $finalPatientId,
                'service_id' => $service->id,
                'acquired_service_id' => $acquiredService->id,
                'pain_intensity' => $historial->rango_dolor,
                'cervical_vertebrae' => implode(', ', $cervicalVertebrae),
                'thoracic_vertebrae' => implode(', ', $thoracicVertebrae),
                'lumbar_vertebrae' => implode(', ', $lumbarVertebrae),
                'created_at' => $historial->fecha,
            ]
        );
    }

    private function migrateHistorialTerapiaFisicas(array $patientIds, array &$results, array $idMapping)
    {
        $itemsMatchTerapiaFisica = [
            1 => PhysicalTherapyCategory::find(48)?->id,
            2 => PhysicalTherapyCategory::find(51)?->id,
            3 => PhysicalTherapyCategory::find(765)?->id,
            4 => PhysicalTherapyCategory::find(765)?->id,
            5 => PhysicalTherapyCategory::find(741)?->id,
            6 => PhysicalTherapyCategory::find(742)?->id,
            8 => PhysicalTherapyCategory::find(60)?->id,
            9 => PhysicalTherapyCategory::find(739)?->id,
            10 => PhysicalTherapyCategory::find(739)?->id,
            11 => PhysicalTherapyCategory::find(890)?->id,
            12 => PhysicalTherapyCategory::find(871)?->id,
            13 => PhysicalTherapyCategory::find(872)?->id,
            14 => PhysicalTherapyCategory::find(947)?->id,
            15 => PhysicalTherapyCategory::find(817)?->id,
            16 => PhysicalTherapyCategory::find(793)?->id,
            17 => PhysicalTherapyCategory::find(794)?->id,
            18 => PhysicalTherapyCategory::find(91)?->id,
            19 => PhysicalTherapyCategory::find(91)?->id,
            20 => PhysicalTherapyCategory::find(921)?->id,
            21 => PhysicalTherapyCategory::find(921)?->id,
            22 => PhysicalTherapyCategory::find(897)?->id,
            23 => PhysicalTherapyCategory::find(898)?->id,
        ];

        $historiales = HistorialTerapia::whereIn('paciente_id', $patientIds)
            ->whereNotIn('id', MedicalTerapiaTracionModule::pluck('id')->toArray())
            ->get();

        foreach ($historiales as $historial) {
            try {
                // Usar el ID mapeado si existe, sino usar el original
                $finalPatientId = $idMapping[$historial->paciente_id] ?? $historial->paciente_id;

                if (!Patient::find($finalPatientId)) {
                    $results['errors'][] = "Paciente no encontrado para historial terapia - ID: {$historial->paciente_id} (mapeado: {$finalPatientId})";
                    continue;
                }

                $user = User::find($historial->user_id);
                if (!$user) {
                    $results['errors'][] = "Usuario no encontrado para historial terapia - ID: {$historial->user_id}";
                    continue;
                }

                $item = Item::where('type_of_item_id', ItemType::TERAPIA_FISICA->value)->first();
                if (!$item) {
                    $item = Item::factory()->create([
                        'type_of_item_id' => ItemType::TERAPIA_FISICA->value,
                    ]);
                }

                $service = PatientItem::create([
                    'id' => $historial->service_id,
                    'patient_id' => $finalPatientId,
                    'item_id' => $item->id,
                    'quantity' => 0,
                    'price' => $item->price,
                    'total' => $item->price,
                    'created_at' => $historial->fecha,
                ]);

                $room = Room::inRandomOrder()->first() ?? Room::factory()->create();
                $bed = Bed::inRandomOrder()->first() ?? Bed::factory()->create();

                $service->waiting_room()->create([
                    'patient_id' => $finalPatientId,
                    'room_id' => $room->id,
                    'bed_id' => $bed->id,
                    'user_id' => $user->id,
                    'created_at' => $historial->fecha,
                ]);

                $acquiredService = AcquiredService::create([
                    'patient_id' => $finalPatientId,
                    'price' => $item->price,
                    'status' => ServicesStatus::COMPLETADA->value,
                    'patient_item_id' => $service->id,
                    'created_at' => $historial->fecha,
                ]);

                $categories = explode(',', $historial->tipo_terapia);

                $medicalTerapiaTracionModule = MedicalTerapiaTracionModule::updateOrCreate(
                    ['id' => $historial->id],
                    [
                        'id' => $historial->id,
                        'patient_id' => $finalPatientId,
                        'service_id' => $service->id,
                        'acquired_service_id' => $acquiredService->id,
                        'created_at' => $historial->fecha,
                    ]
                );

                foreach ($categories as $category) {
                    if (isset($itemsMatchTerapiaFisica[$category]) && $itemsMatchTerapiaFisica[$category]) {
                        $medicalTerapiaTracionModule->physical_therapy_category()->attach($itemsMatchTerapiaFisica[$category]);
                    }
                }

                $results['migrated_therapies']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Error migrando historial terapia {$historial->id}: " . $e->getMessage();
            }
        }
    }

    // Métodos utilitarios
    private function parseDateInt($timestamp)
    {
        try {
            if (empty($timestamp) || is_null($timestamp)) {
                return \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            }

            if (is_numeric($timestamp)) {
                return \Carbon\Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
            }

            if (is_string($timestamp) && ctype_digit(trim($timestamp))) {
                return \Carbon\Carbon::createFromTimestamp((int) $timestamp)->format('Y-m-d H:i:s');
            }

            if (is_string($timestamp)) {
                $parsedDate = \Carbon\Carbon::parse($timestamp);
                return $parsedDate->format('Y-m-d H:i:s');
            }

            return \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            return \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        }
    }

    private function parseDate($timestamp)
    {
        try {
            if (empty($timestamp) || is_null($timestamp) || trim($timestamp) === '') {
                return now();
            }

            if (is_numeric($timestamp) && $timestamp == 0) {
                return now();
            }

            if (is_string($timestamp) && preg_match('/^-?\d{1,4}-/', $timestamp)) {
                $year = (int) substr($timestamp, 0, strpos($timestamp, '-', 1));
                if ($year <= 0) {
                    return now();
                }
            }

            if (is_string($timestamp) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $timestamp)) {
                return \Carbon\Carbon::createFromFormat('d-m-Y', $timestamp);
            }

            if (is_string($timestamp) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $timestamp)) {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $timestamp);
            }

            if (is_string($timestamp) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $timestamp)) {
                try {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $timestamp);
                } catch (\Exception $e) {
                    return \Carbon\Carbon::createFromFormat('m/d/Y', $timestamp);
                }
            }

            if (is_numeric($timestamp)) {
                $timestampInt = (int) $timestamp;
                if ($timestampInt >= 0 && $timestampInt <= 4102444800) {
                    return \Carbon\Carbon::createFromTimestamp($timestampInt);
                }
            }

            $parsedDate = \Carbon\Carbon::parse($timestamp);

            if ($parsedDate->year >= 1900 && $parsedDate->year <= 2100) {
                return $parsedDate;
            }

            return now();

        } catch (\Exception $e) {
            return now();
        }
    }

    private function mapSexo($sexo)
    {
        return match (strtolower($sexo)) {
            'masculino', 'm' => 'M',
            'femenino', 'f' => 'F',
            default => null,
        };
    }

    private function generateRandomCode($model, $length = 8, $field = 'code')
    {
        do {
            $code = '';
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while ($model::where($field, $code)->exists());

        return $code;
    }

    /**
     * OPTIMIZADO: Construye un mapa de referencias para evitar similar_text en bucles
     */
    private function buildReferenceMap($whereHeMetUsOptions)
    {
        $map = [];

        foreach ($whereHeMetUsOptions as $option) {
            $optionName = strtolower(trim($option->name));

            // Mapeo directo por nombre exacto
            $map[$optionName] = $option->id;

            // Mapeo por palabras clave comunes
            $keywords = explode(' ', $optionName);
            foreach ($keywords as $keyword) {
                if (strlen($keyword) >= 3) { // Solo palabras de 3+ caracteres
                    if (!isset($map[$keyword])) {
                        $map[$keyword] = $option->id;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * OPTIMIZADO: Determina el ID final usando datos pre-cargados
     */
    private function determineFinalPatientIdOptimized($legacyPatient, &$existingPatientIds, $existingPatientsByName)
    {
        $originalId = $legacyPatient->id;
        $legacyFullName = strtolower(trim(($legacyPatient->nombre ?? '') . ' ' . ($legacyPatient->apellido ?? '')));

        // Verificar si ya existe un paciente con el mismo nombre completo
        if (isset($existingPatientsByName[$legacyFullName])) {
            return null; // Ya existe un paciente con el mismo nombre
        }

        // Verificar si el ID original existe
        if (!in_array($originalId, $existingPatientIds)) {
            // Agregar el ID al array para futuras verificaciones
            $existingPatientIds[] = $originalId;
            return $originalId; // El ID no existe, usar el original
        }

        // El ID existe, generar nuevo ID con prefijo incremental
        $idExtra = 1; // Empezar desde 1, no 0
        $newId = $idExtra . $originalId;

        // Verificar que el nuevo ID no exista y actualizar dentro del bucle
        while (in_array($newId, $existingPatientIds)) {
            $idExtra++;
            $newId = $idExtra . $originalId;
        }

        // Agregar el nuevo ID al array para futuras verificaciones
        $existingPatientIds[] = $newId;

        return $newId;
    }

    /**
     * Update assigned plan data including therapies and sessions
     * This endpoint allows correction of consumed therapies and sessions
     */
    public function updateAssignedPlan(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'therapies_number' => 'nullable|integer|min:0',
            'consumed_therapies' => 'nullable|integer|min:0',
            'total_sessions' => 'nullable|integer|min:0',
            'consumed_sessions' => 'nullable|integer|min:0',
            'amount' => 'nullable|numeric|min:0',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',

            //nuevos
            'consume' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric|min:0',
            'paid' => 'nullable|numeric|min:0',
            'descuent' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Find the assigned plan
            $assignedPlan = AssignedPlan::with(['plan', 'patient', 'services', 'voucher'])
                ->where('patient_id', $request->patient_id)
                ->first();

            if (!$assignedPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan asignado no encontrado para el paciente ' . $request->patient_id
                ], 404);
            }
            // Store original values for comparison
            $originalTherapiesNumber = $assignedPlan->therapies_number;
            $originalTotalSessions = $assignedPlan->total_sessions;
            // Get current consumed counts
            $currentConsumedSessions = $assignedPlan->patient->acquired_services()
                ->whereNotNull('plan_item_id')
                ->whereNotNull('assigned_plan_id')
                ->whereHas('patient_plan_item', function ($query) {
                    $query->where('type_of_item_id', ItemType::AJUSTE->value);
                })
                ->where('assigned_plan_id', $assignedPlan->id)
                ->count();

            $currentConsumedTherapies = $assignedPlan->patient->acquired_services()
                ->whereNotNull('plan_item_id')
                ->whereNotNull('assigned_plan_id')
                ->whereHas('patient_plan_item', function ($query) {
                    $query->where('type_of_item_id', ItemType::TERAPIA_FISICA->value);
                })
                ->where('assigned_plan_id', $assignedPlan->id)
                ->count();

            // Update basic plan data if provided
            if ($request->has('therapies_number')) {
                $assignedPlan->therapies_number = $request->therapies_number;
            }

            if ($request->has('total_sessions')) {
                $assignedPlan->total_sessions = $request->total_sessions;
            }

            if ($request->has('amount')) {
                $assignedPlan->amount = $request->amount;
            }

            if ($request->has('date_start')) {
                $assignedPlan->date_start = $request->date_start;
            }

            if ($request->has('date_end')) {
                $assignedPlan->date_end = $request->date_end;
            }

            if ($request->has('consume')) {
                $this->updatePlanConsume($assignedPlan, $request->consume);
            }

            if ($request->has('balance')) {
                $this->updatePlanBalance($assignedPlan, $request->balance);
            }

            if ($request->has('descuent')) {
              //  $descuents = $assignedPlan->descuentAuthorizations()->whereIn('status', [AuthorizationStatus::AUTORIZADO->value, AuthorizationStatus::APROBADO->value])->get();

                $user = Role::find(1)->users()->first();

                DescuentAuthorization::create([
                    'patient_id' => $assignedPlan->patient_id,
                    'assigned_plan_id' => $assignedPlan->id,
                    'type' => 1,
                    'request_amount' => $request->descuent,
                    'approved_amount' => $request->descuent,
                    'status' => AuthorizationStatus::AUTORIZADO->value,
                    'request_by' => $user->id,
                    'authorized_by' => $user->id,
                    'authorized_at' => now(),
                ]);
            }

            // Calculate new item price based on updated values
            $total_items = $assignedPlan->total_sessions + $assignedPlan->therapies_number;
            $item_price = $total_items != 0 ? $assignedPlan->amount / $total_items : 0;

            // Handle sessions adjustment
            if ($request->has('consumed_sessions')) {
                $targetConsumedSessions = $request->consumed_sessions;
                $sessionsDifference = $targetConsumedSessions - $currentConsumedSessions;

                if ($sessionsDifference > 0) {
                    // Need to add more consumed sessions
                    $itemAjuste = Item::where('plan', true)
                        ->where('type_of_item_id', ItemType::AJUSTE->value)
                        ->first();

                    if ($itemAjuste) {
                        for ($i = 0; $i < $sessionsDifference; $i++) {
                            AcquiredService::create([
                                'patient_id' => $assignedPlan->patient_id,
                                'assigned_plan_id' => $assignedPlan->id,
                                'plan_item_id' => $itemAjuste->id,
                                'price' => $item_price,
                                'status' => ServicesStatus::COMPLETADA->value,
                                'created_at' => now(),
                            ]);
                        }
                    }
                } elseif ($sessionsDifference < 0) {
                    // Need to remove consumed sessions
                    $sessionsToDelete = abs($sessionsDifference);

                    $servicesToDelete = $assignedPlan->patient->acquired_services()
                        ->whereNotNull('plan_item_id')
                        ->whereNotNull('assigned_plan_id')
                        ->whereHas('patient_plan_item', function ($query) {
                            $query->where('type_of_item_id', ItemType::AJUSTE->value);
                        })
                        ->where('assigned_plan_id', $assignedPlan->id)
                        ->orderBy('created_at', 'desc')
                        ->take($sessionsToDelete)
                        ->get();

                    foreach ($servicesToDelete as $service) {
                        $service->forceDelete();
                    }
                }
            }

            // Handle therapies adjustment
            if ($request->has('consumed_therapies')) {
                $targetConsumedTherapies = $request->consumed_therapies;
                $therapiesDifference = $targetConsumedTherapies - $currentConsumedTherapies;

                if ($therapiesDifference > 0) {
                    // Need to add more consumed therapies
                    $itemTerapia = Item::where('plan', true)
                        ->where('type_of_item_id', ItemType::TERAPIA_FISICA->value)
                        ->first();

                    if ($itemTerapia) {
                        for ($i = 0; $i < $therapiesDifference; $i++) {
                            AcquiredService::create([
                                'patient_id' => $assignedPlan->patient_id,
                                'assigned_plan_id' => $assignedPlan->id,
                                'plan_item_id' => $itemTerapia->id,
                                'price' => $item_price,
                                'status' => ServicesStatus::COMPLETADA->value,
                                'created_at' => now(),
                            ]);
                        }
                    }
                } elseif ($therapiesDifference < 0) {
                    // Need to remove consumed therapies
                    $therapiesToDelete = abs($therapiesDifference);

                    $servicesToDelete = $assignedPlan->patient->acquired_services()
                        ->whereNotNull('plan_item_id')
                        ->whereNotNull('assigned_plan_id')
                        ->whereHas('patient_plan_item', function ($query) {
                            $query->where('type_of_item_id', ItemType::TERAPIA_FISICA->value);
                        })
                        ->where('assigned_plan_id', $assignedPlan->id)
                        ->orderBy('created_at', 'desc')
                        ->take($therapiesToDelete)
                        ->get();

                    foreach ($servicesToDelete as $service) {
                        $service->forceDelete();
                    }
                }
            }

            // Update vouchers if amount or consumed items changed
            if ($request->has('amount') || $request->has('consumed_sessions') || $request->has('consumed_therapies')) {
                $this->updateVouchers($assignedPlan, $item_price);
            }

            // Save the assigned plan
            $assignedPlan->save();

            // Check if plan is completed
            $assignedPlan->isCompleted();

            DB::commit();

            // Reload with fresh data
            $assignedPlan->load(['services', 'voucher']);

            // Calculate final counts for response
            $finalConsumedSessions = $assignedPlan->patient->acquired_services()
                ->whereNotNull('plan_item_id')
                ->whereNotNull('assigned_plan_id')
                ->whereHas('patient_plan_item', function ($query) {
                    $query->where('type_of_item_id', ItemType::AJUSTE->value);
                })
                ->where('assigned_plan_id', $assignedPlan->id)
                ->count();

            $finalConsumedTherapies = $assignedPlan->patient->acquired_services()
                ->whereNotNull('plan_item_id')
                ->whereNotNull('assigned_plan_id')
                ->whereHas('patient_plan_item', function ($query) {
                    $query->where('type_of_item_id', ItemType::TERAPIA_FISICA->value);
                })
                ->where('assigned_plan_id', $assignedPlan->id)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Plan asignado actualizado exitosamente',
                'data' => [
                    'assigned_plan_id' => $assignedPlan->id,
                    'patient_id' => $assignedPlan->patient_id,
                    'plan_id' => $assignedPlan->plan_id,
                    'therapies_number' => $assignedPlan->therapies_number,
                    'total_sessions' => $assignedPlan->total_sessions,
                    'consumed_therapies' => $finalConsumedTherapies,
                    'consumed_sessions' => $finalConsumedSessions,
                    'remaining_therapies' => max(0, $assignedPlan->therapies_number - $finalConsumedTherapies),
                    'remaining_sessions' => max(0, $assignedPlan->total_sessions - $finalConsumedSessions),
                    'amount' => $assignedPlan->amount,
                    'status' => $assignedPlan->status,
                    'date_start' => $assignedPlan->date_start,
                    'date_end' => $assignedPlan->date_end,
                    'item_price' => $item_price,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando plan asignado: ' . $e->getMessage(), [
                'assigned_plan_id' => $assignedPlan->id,
                'patient_id' => $assignedPlan->patient_id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el plan asignado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateAssignedPlansFromLegacy(Request $request)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1000);

        $request->validate([
            'patient_ids' => 'array',
            'patient_ids.*' => 'integer',
            'assigned_plan_ids' => 'array',
            'assigned_plan_ids.*' => 'integer',
            'branch_ids' => 'array',
            'branch_ids.*' => 'integer',
            'check_all' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $results = [
                'success' => true,
                'checked_plans' => 0,
                'updated_plans' => 0,
                'differences_found' => [],
                'errors' => []
            ];

            // Determinar qué planes revisar
            if ($request->has('assigned_plan_ids') && !empty($request->assigned_plan_ids)) {
                // Revisar planes específicos
                $assignedPlans = AssignedPlan::whereIn('id', $request->assigned_plan_ids)->get();
            } elseif ($request->has('patient_ids') && !empty($request->patient_ids)) {
                // Revisar planes de pacientes específicos
                $assignedPlans = AssignedPlan::whereIn('patient_id', $request->patient_ids)->get();
            } elseif ($request->has('branch_ids') && !empty($request->branch_ids)) {
                // Revisar planes de pacientes por branch_id
                $assignedPlans = AssignedPlan::whereHas('patient', function($query) use ($request) {
                    $query->whereIn('branch_id', $request->branch_ids);
                })->get();
            } elseif ($request->check_all) {
                // Revisar todos los planes (con límite de seguridad)
                $assignedPlans = AssignedPlan::limit(1000)->get();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe especificar patient_ids, assigned_plan_ids, branch_ids o check_all=true'
                ], 400);
            }

            foreach ($assignedPlans as $assignedPlan) {
                try {
                    $results['checked_plans']++;

                    // Buscar el plan en la base de datos legacy
                    $legacyPlan = Ajuste::where('id', $assignedPlan->id)->first();

                    if (!$legacyPlan) {
                        $results['errors'][] = "Plan asignado {$assignedPlan->id} no encontrado en base de datos legacy";
                        continue;
                    }

                    $differences = [];
                    $needsUpdate = false;

                    // Comparar total_sessions (viene del plan actual)
                    $currentTotalSessions = $assignedPlan->total_sessions ?? 0;
                    $legacyTotalSessions = $legacyPlan->ajustes ?? 0; // En legacy viene del plan también

                    // Comparar therapies_number
                    $currentTherapiesNumber = $assignedPlan->therapies_number ?? 0;
                    $legacyTherapiesNumber = $legacyPlan->terapias_fisicas ?? 0;

                    if ($currentTherapiesNumber != $legacyTherapiesNumber) {
                        $differences['therapies_number'] = [
                            'current' => $currentTherapiesNumber,
                            'legacy' => $legacyTherapiesNumber
                        ];
                        $needsUpdate = true;
                    }

                    if ($currentTotalSessions != $legacyTotalSessions) {
                        $differences['total_sessions'] = [
                            'current' => $currentTotalSessions,
                            'legacy' => $legacyTotalSessions
                        ];
                        $needsUpdate = true;
                    }

                    // Comparar otros campos importantes
                    $currentAmount = $assignedPlan->amount ?? 0;
                    $legacyAmount = $legacyPlan->costo ?? 0;

                    if (abs($currentAmount - $legacyAmount) > 0.01) {
                        $differences['amount'] = [
                            'current' => $currentAmount,
                            'legacy' => $legacyAmount
                        ];
                        $needsUpdate = true;
                    }

                    // Comparar fechas
                    /*
                    $currentDateStart = $assignedPlan->date_start;
                    $legacyDateStart = $this->parseDate($legacyPlan->fecha_ciclo_insertada);

                    if ($currentDateStart != $legacyDateStart) {
                        $differences['date_start'] = [
                            'current' => $currentDateStart,
                            'legacy' => $legacyDateStart
                        ];
                        $needsUpdate = true;
                    }

                    $currentDateEnd = $assignedPlan->date_end;
                    $legacyDateEnd = $this->parseDate($legacyPlan->fecha_expiracion);

                    if ($currentDateEnd != $legacyDateEnd) {
                        $differences['date_end'] = [
                            'current' => $currentDateEnd,
                            'legacy' => $legacyDateEnd
                        ];
                        $needsUpdate = true;
                    }
                    */

                    // Si hay diferencias, actualizar el plan
                    if ($needsUpdate) {
                        $assignedPlan->update([
                            'therapies_number' => $legacyTherapiesNumber,
                            'total_sessions' => $legacyTotalSessions,
                            'amount' => $legacyAmount,
                    //        'date_start' => $legacyDateStart,
                      //      'date_end' => $legacyDateEnd,
                        ]);

                        $assignedPlan->save();

                        $results['updated_plans']++;
                        $results['differences_found'][] = [
                            'assigned_plan_id' => $assignedPlan->id,
                            'patient_id' => $assignedPlan->patient_id,
                            'patient_name' => $assignedPlan->patient->first_name . ' ' . $assignedPlan->patient->last_name ?? 'N/A',
                            'plan_name' => $assignedPlan->plan_name,
                            'differences' => $differences
                        ];

                    }

                } catch (\Exception $e) {
                    $results['errors'][] = "Error procesando plan {$assignedPlan->id}: " . $e->getMessage();
                    Log::error("Error actualizando plan asignado desde legacy", [
                        'assigned_plan_id' => $assignedPlan->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            DB::commit();

            return response()->json($results);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en actualización de planes asignados desde legacy: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error durante la actualización: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    public function changeTypeOfPatient(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'type' => 'required|integer|exists:type_of_appointments,id|in:1,2,3,4',
        ]);

        $patient = Patient::find($request->patient_id);

        $TypeAppointment = $request->type != 1 ? $request->type - 1 : AppointmentType::CONSULTA->value;

        if ($patient->appointments()->latest()->where('type_of_appointment_id', $TypeAppointment)->where('status_id', AppointmentStatus::COMPLETADA->value)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'La ultima cita del paciente es de este tipo'
            ], 400);
        }

        Appointment::create([
            'note' => 'Cita de migración agregada manualmente',
            'patient_id' => $patient->id,
            'branch_id' => $patient->branch_id,
            'type_of_appointment_id' => $TypeAppointment,
            'status_id' => AppointmentStatus::COMPLETADA->value,
            'date' => now()->format('Y-m-d'),
            'hour' => now()->format('H:i:s'),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de paciente cambiado exitosamente, ahora es de tipo ' . TypeOfAppointments::find($TypeAppointment + 1)->name
        ], 200);

    }


    public function deleteAssignedPlanForPatientBranch(Request $request)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1000);

        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        try {
            DB::beginTransaction();

            $assignedPlans = AssignedPlan::whereHas('patient', function($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })->get();


            foreach ($assignedPlans as $assignedPlan) {
                $assignedPlan->installments()->forceDelete();
                $assignedPlan->appointments()->update(['assigned_plan_id' => null]);
                $assignedPlan->services()->forceDelete();
                $assignedPlan->ScheduledAppointments()->forceDelete();
                $assignedPlan->voucher()->each(function($voucher) {
                    $voucher->plan_items()->detach();
                    $voucher->patient_items()->detach();
                });
                $assignedPlan->voucher()->forceDelete();
                $assignedPlan->descuentAuthorizations()->forceDelete();
                $assignedPlan->planConsume()->forceDelete();
                $assignedPlan->transactions()->forceDelete();
                $assignedPlan->forceDelete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Planes asignados eliminados exitosamente'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error eliminando planes asignados: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error durante la eliminación: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Update vouchers for the assigned plan based on new consumed amount
     */
    private function updateVouchers($assignedPlan, $item_price)
    {
        // Get current consumed services count
        $consumedSessions = $assignedPlan->patient->acquired_services()
            ->whereNotNull('plan_item_id')
            ->whereNotNull('assigned_plan_id')
            ->whereHas('patient_plan_item', function ($query) {
                $query->where('type_of_item_id', ItemType::AJUSTE->value);
            })
            ->where('assigned_plan_id', $assignedPlan->id)
            ->count();

        $consumedTherapies = $assignedPlan->patient->acquired_services()
            ->whereNotNull('plan_item_id')
            ->whereNotNull('assigned_plan_id')
            ->whereHas('patient_plan_item', function ($query) {
                $query->where('type_of_item_id', ItemType::TERAPIA_FISICA->value);
            })
            ->where('assigned_plan_id', $assignedPlan->id)
            ->count();

        $totalConsumedItems = $consumedSessions + $consumedTherapies;
        $totalConsumedAmount = $totalConsumedItems * $item_price;

        // Delete all existing vouchers
        Voucher::where('assigned_plan_id', $assignedPlan->id)->forceDelete();

        // Create new vouchers based on consumed amount
        if ($totalConsumedAmount > 0 && $totalConsumedItems > 0) {
            for ($i = 0; $i < $totalConsumedItems; $i++) {
                Voucher::create([
                    'assigned_plan_id' => $assignedPlan->id,
                    'status' => 3, // Used status
                    'quantity' => 1,
                    'price' => $item_price,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function updatePlanConsume($assignedPlan, $consume)
    {
        $total_items = $assignedPlan->total_sessions + $assignedPlan->therapies_number;
        $item_price = $total_items != 0 ? $assignedPlan->amount / $total_items : 0;
        $total_consumed_items = (int) $assignedPlan->total_sessions + (int) $assignedPlan->therapies_number;

        // Crear vouchers para que count(vouchers) * $item_price = $p->consumido
        if ($consume > 0 && $item_price > 0) {
            Voucher::where('assigned_plan_id', $assignedPlan->id)->forceDelete();
            // Calcular cuántos vouchers necesitamos: consumido / precio_por_item
            $vouchers_needed = round($consume / $item_price);

            // Asegurar que no creamos más vouchers de los que realmente se consumieron
            $vouchers_needed = min($vouchers_needed, $total_consumed_items);

            for ($i = 0; $i < $vouchers_needed; $i++) {
                Voucher::create([
                    'assigned_plan_id' => $assignedPlan->id,
                    'status' => 3,
                    'quantity' => 1,
                    'price' => $item_price,
                ]);
            }
        } elseif ($consume > 0 && $total_consumed_items > 0) {
            // Si item_price es 0 pero hay consumo, crear vouchers con el precio unitario del consumo
            $price_per_voucher = $consume / $total_consumed_items;

            for ($i = 0; $i < $total_consumed_items; $i++) {
                Voucher::create([
                    'assigned_plan_id' => $assignedPlan->id,
                    'status' => 3,
                    'quantity' => 1,
                    'price' => $price_per_voucher,
                ]);
            }
        }
    }

    private function updatePlanBalance($assignedPlan, $balance)
    {

    }

    /**
     * Determina el ID final que se usará para el paciente basado en las condiciones:
     * - Si el ID existe en Patient y el nombre es diferente, agregar 0 al inicio
     * - Si el nombre del paciente legacy ya existe en la DB, retornar null
     * - Si el ID no existe, usar el ID original
     */
    private function determineFinalPatientId($legacyPatient)
    {
        $originalId = $legacyPatient->id;
        $legacyFullName = trim(($legacyPatient->nombre ?? '') . ' ' . ($legacyPatient->apellido ?? ''));

        // Verificar si ya existe un paciente con el mismo nombre completo en la DB
        $existingPatientByName = Patient::where(function ($query) use ($legacyPatient) {
            $query->where('first_name', $legacyPatient->nombre ?? '')
                ->where('last_name', $legacyPatient->apellido ?? '');
        })->first();

        if ($existingPatientByName) {
            // Ya existe un paciente con el mismo nombre, no migrar
            return null;
        }

        // Verificar si el ID original existe en la tabla Patient
        $existingPatientById = Patient::find($originalId);

        if (!$existingPatientById) {
            // El ID no existe, usar el ID original
            return $originalId;
        }

        // El ID existe, verificar si el nombre es diferente
        $existingFullName = trim($existingPatientById->first_name . ' ' . $existingPatientById->last_name);

        if (strtolower($existingFullName) === strtolower($legacyFullName)) {
            // Mismo ID y mismo nombre, no migrar (ya existe)
            return null;
        }

        // Mismo ID pero nombre diferente, agregar 0 al inicio
        $newId = '0' . $originalId;

        // Verificar que el nuevo ID no exista ya
        while (Patient::find($newId)) {
            $newId = '0' . $newId;
        }

        return $newId;
    }
}
