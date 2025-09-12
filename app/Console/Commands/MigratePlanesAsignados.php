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
    protected $signature = 'migrate:planes-asignados';
    protected $description = 'Migrar datos desde planes asignados (legacy) hacia planes asignados (nuevo)';
    protected $ignored_plan = [461, 462, 458, 434, 435, 436, 437, 438, 439, 441, 442, 443, 444, 445, 446, 453, 454, 455, 456, 412, 416, 417, 419, 420, 422, 423, 426, 428, 395, 396, 397, 398, 400, 401, 402, 404, 406, 407, 399, 355, 354, 353, 352, 351, 350, 349, 347, 346, 344, 343, 341, 337, 336, 335, 329, 328, 327, 326, 325, 324, 323, 322, 314, 313, 311, 309, 308, 299, 287, 286, 285, 283, 278, 277, 276, 275, 274, 273, 268, 267, 266, 265, 264, 263, 262, 261, 258, 257, 256, 255, 254, 253, 252, 251, 250, 249, 248, 247, 246, 244, 243, 242, 241, 240];


    public function handle()
    {
        $this->info("Iniciando migración de planes asignados...");

        Ajuste::
        whereNotIn('plan_id', $this->ignored_plan)
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

                    // Verificar si el paciente existe
                    if (!Patient::find($p->paciente_id)) {
                        $this->warn("Paciente no encontrado - ID: {$p->paciente_id}. Omitiendo registro.");
                        continue;
                    }

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

                    // Calcular precio por ítem como en la función find()
                    $total_items = $assignedPlan->plan->total_sessions + $assignedPlan->therapies_number;
                    $item_price = $total_items != 0 ? $assignedPlan->amount / $total_items : 0;

                    // Calcular cuántos vouchers necesitamos crear para que el consumo sea igual a $p->consumido
                    $total_consumed_items = (int) $p->sesiones_utilizadas + (int) $p->terapias_utilizadas;

                    // Crear vouchers para que count(vouchers) * $item_price = $p->consumido
                    if ($p->consumido > 0 && $item_price > 0) {
                        // Calcular cuántos vouchers necesitamos: consumido / precio_por_item
                        $vouchers_needed = round($p->consumido / $item_price);

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
                        // Si item_price es 0 pero hay consumo, crear vouchers con el precio unitario del consumo
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

                    // Usar el precio por ítem para todos los servicios adquiridos
                    $priceAjuste = $item_price;
                    $priceTerapia = $item_price;

                    if ($p->sesiones_utilizadas != 0) {
                        $itemAjuste = Item::where('plan', true)->where('type_of_item_id', ItemType::AJUSTE->value)->first();
                        $sessiones = (int) $p->sesiones_utilizadas;
                        for ($i = 0; $i < $sessiones; $i++) {
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

        $this->info("Migración completada.");
    }



}
