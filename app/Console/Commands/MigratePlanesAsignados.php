<?php
namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Plan;
use App\Models\User;
use App\Enums\ItemType;
use App\Models\Patient;
use App\Models\Voucher;
use App\Enums\PlanStatus;
use App\Enums\ServicesStatus;
use App\Models\Legacy\Ajuste;
use App\Models\{AssignedPlan};
use App\Models\AcquiredService;
use App\Models\DescuentAuthorization;


class MigratePlanesAsignados extends BaseCommand
{
    protected $signature = 'migrate:planes-asignados {--test-plan= : Test specific plan ID}';
    protected $description = 'Migrar datos desde planes asignados (legacy) hacia planes asignados (nuevo)';
    protected $ignored_plan = [461, 462, 458, 434, 435, 436, 437, 438, 439, 441, 442, 443, 444, 445, 446, 453, 454, 455, 456, 412, 416, 417, 419, 420, 422, 423, 426, 428, 395, 396, 397, 398, 400, 401, 402, 404, 406, 407, 399, 355, 354, 353, 352, 351, 350, 349, 347, 346, 344, 343, 341, 337, 336, 335, 329, 328, 327, 326, 325, 324, 323, 322, 314, 313, 311, 309, 308, 299, 287, 286, 285, 283, 278, 277, 276, 275, 274, 273, 268, 267, 266, 265, 264, 263, 262, 261, 258, 257, 256, 255, 254, 253, 252, 251, 250, 249, 248, 247, 246, 244, 243, 242, 241, 240];


    public function handle()
    {
        $this->info("Iniciando migración de planes asignados...");

        Ajuste::
            whereIn('paciente_id', Patient::whereDoesntHave('assigned_plan')->pluck('id')->toArray())
            ->whereIn('plan_id', Plan::whereNotIn('id', $this->ignored_plan)->pluck('id')->toArray())
            ->chunk(500, function ($pacientes) {
                $user = User::first();
                foreach ($pacientes as $p) {
                    if (in_array($p->estado, [1, 2, 3])) {

                        $planStatusMatch = [
                            1 => PlanStatus::Activo->value,
                            2 => PlanStatus::Expirado->value,
                            3 => PlanStatus::Completado->value,
                            4 => PlanStatus::Desactivado->value,
                        ];

                        if (!Plan::find($p->plan_id)) {
                            $this->warn("Plan no encontrado - ID: {$p->plan_id}. Omitiendo registro.");
                            continue;
                        }

                        $assignedPlan = AssignedPlan::create(
                            [
                                'id' => $p->id,
                                'plan_id' => $p->plan_id,
                                'patient_id' => $p->paciente_id,
                                'date_start' => $this->parseDate($p->fecha_ciclo_insertada),
                                'date_end' => $this->parseDate($p->fecha_expiracion),
                                'plan_name' => Plan::find($p->plan_id)->name ?? 'Plan ' . $this->generateRandomCode(AssignedPlan::class, 8, 'plan_name'),
                                'paid_type' => 1,
                                'amount' => $p->costo,
                                'therapies_number' => $p->terapias_fisicas,
                                'total_sessions' => $p->ajustes,
                                'number_installments' => Plan::find($p->plan_id)->number_installments ?? 0,
                                'status' => $planStatusMatch[$p->estado],
                                'branch_id' => $p->centro_id,
                                'user_id' => $user->id,
                                'card_commission' => $p->card_fee,
                                'bank_commission' => $p->bank_fee,
                                'other_commission' => $p->other_fee,
                                'created_at' => $this->parseDateInt($p->fecha_cre),
                                'updated_at' => $this->parseDateInt($p->fecha_cre),
                            ]
                        );
                        //balance=pagado-consumido

                        $assignedPlan->transactions()->create([
                            'assigned_plan_id' => $assignedPlan->id,
                            'patient_id' => $p->paciente_id,
                            'amount' => $p->pagado,
                            'transaction_type' => 'entrada',
                            'description' => 'Plan asignado',
                        ]);

                        //Aqui se maneja el descuento que tiene
                        if ($p->descuento != 0) {
                            DescuentAuthorization::create([
                                'patient_id' => $p->paciente_id,
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

                        // Calcular precio por ítem - CORREGIDO: usar valores del AssignedPlan
                        $total_items = ($p->ajustes ?? 0) + ($p->terapias_fisicas ?? 0);
                        $item_price = $total_items != 0 ? $assignedPlan->amount / $total_items : 0;

                        // Calcular cuántos vouchers necesitamos crear para que el consumo sea igual a $p->consumido
                        $total_consumed_items = (int) $p->sesiones_utilizadas + (int) $p->terapias_utilizadas;

                        // Crear vouchers - MEJORADO: Priorizar exactitud del consumido
                        if ($p->consumido > 0 && $total_consumed_items > 0) {
                            // Siempre usar el precio exacto basado en el consumido real
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

                        // VALIDACIÓN: Verificar que el consumido migrado sea exactamente igual al legacy
                        $migrated_consumed = Voucher::where('assigned_plan_id', $assignedPlan->id)->sum('price');

                        if (abs($migrated_consumed - $p->consumido) > 0.01) { // Tolerancia de 1 centavo por redondeo
                            $this->error("ERROR: Plan ID {$assignedPlan->id} - Consumido no coincide!");
                            $this->error("  Legacy consumido: {$p->consumido}");
                            $this->error("  Migrado consumido: {$migrated_consumed}");
                            $this->error("  Diferencia: " . ($migrated_consumed - $p->consumido));

                            // Eliminar vouchers creados para este plan
                            Voucher::where('assigned_plan_id', $assignedPlan->id)->forceDelete();

                            // Eliminar el plan asignado creado
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

                            $this->error("Plan {$assignedPlan->id} eliminado debido a inconsistencia en consumido.");
                            continue; // Saltar al siguiente plan
                        }

                        $this->info("✓ Plan ID {$assignedPlan->id} - Consumido verificado: {$migrated_consumed} = {$p->consumido}");

                        $priceAjuste = $total_consumed_items > 0 ? $p->consumido / $total_consumed_items : $item_price;
                        $priceTerapia = $priceAjuste;

                        $used_sessions = $assignedPlan->patient->acquired_services()
                            ->whereNotNull('plan_item_id')
                            ->whereNotNull('assigned_plan_id')
                            ->whereHas('patient_plan_item', function ($query) {
                                $query->where('type_of_item_id', ItemType::AJUSTE->value);
                            })
                            ->where('assigned_plan_id', $assignedPlan->id)
                            ->count();

                        $used_therapies = $assignedPlan->patient->acquired_services()
                            ->whereNotNull('plan_item_id')
                            ->whereNotNull('assigned_plan_id')
                            ->whereHas('patient_plan_item', function ($query) {
                                $query->where('type_of_item_id', ItemType::TERAPIA_FISICA->value);
                            })
                            ->where('assigned_plan_id', $assignedPlan->id)
                            ->count();

                        if ($p->sesiones_utilizadas != 0) {
                            $itemAjuste = Item::where('plan', true)->where('type_of_item_id', ItemType::AJUSTE->value)->first();
                            $sessiones = (int) $p->sesiones_utilizadas;
                            for ($i = 0; $i < $sessiones; $i++) {
                                if ($used_sessions >= $p->sesiones_utilizadas)
                                    break;

                                AcquiredService::create([
                                    'patient_id' => $p->paciente_id,
                                    'assigned_plan_id' => $assignedPlan->id,
                                    'plan_item_id' => $itemAjuste->id,
                                    'price' => $priceAjuste,
                                    'status' => ServicesStatus::COMPLETADA->value,
                                ]);
                            }
                        }

                        if ($p->terapias_utilizadas != 0) {
                            $itemTerapia = Item::where('plan', true)->where('type_of_item_id', ItemType::TERAPIA_FISICA->value)->first();

                            $terapias = (int) $p->terapias_utilizadas;
                            for ($i = 0; $i < $terapias; $i++) {
                                if ($used_therapies >= $p->terapias_utilizadas)
                                    break;
                                AcquiredService::create([
                                    'patient_id' => $p->paciente_id,
                                    'assigned_plan_id' => $assignedPlan->id,
                                    'plan_item_id' => $itemTerapia->id,
                                    'price' => $priceTerapia,
                                    'status' => ServicesStatus::COMPLETADA->value,
                                ]);
                            }
                        }


                    }

                }
            });

        // Validación final: Verificar todos los planes migrados
        $this->info("Realizando validación final de consumidos...");

        $total_plans = 0;
        $valid_plans = 0;
        $invalid_plans = 0;

        AssignedPlan::chunk(100, function ($assignedPlans) use (&$total_plans, &$valid_plans, &$invalid_plans) {
            foreach ($assignedPlans as $assignedPlan) {
                $total_plans++;

                // Buscar el plan legacy correspondiente
                $legacyPlan = Ajuste::where('id', $assignedPlan->id)->first();

                if ($legacyPlan) {
                    $migrated_consumed = Voucher::where('assigned_plan_id', $assignedPlan->id)->sum('price');

                    if (abs($migrated_consumed - $legacyPlan->consumido) <= 0.01) {
                        $valid_plans++;
                    } else {
                        $invalid_plans++;
                        $this->warn("Plan {$assignedPlan->id}: Legacy={$legacyPlan->consumido}, Migrado={$migrated_consumed}");
                    }
                }
            }
        });

        $this->info("=== RESUMEN DE VALIDACIÓN ===");
        $this->info("Total planes verificados: {$total_plans}");
        $this->info("Planes válidos: {$valid_plans}");
        $this->info("Planes inválidos: {$invalid_plans}");

        if ($invalid_plans > 0) {
            $this->error("¡ATENCIÓN! {$invalid_plans} planes tienen inconsistencias en el consumido.");
        } else {
            $this->info("✓ Todos los planes tienen consumidos correctos.");
        }

        $this->info("Migración de planes asignados completada.");
    }



}
