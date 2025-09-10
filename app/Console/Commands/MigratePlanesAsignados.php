<?php
namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Plan;
use App\Models\User;
use App\Enums\ItemType;
use App\Models\Patient;
use App\Models\Voucher;
use App\Enums\ServicesStatus;
use App\Models\Legacy\Ajuste;
use App\Models\Legacy\Planes;
use App\Models\{AssignedPlan};
use App\Models\AcquiredService;
use Illuminate\Support\Facades\Log;
use App\Models\DescuentAuthorization;

class MigratePlanesAsignados extends BaseCommand
{
    protected $signature = 'migrate:planes-asignados';
    protected $description = 'Migrar datos desde planes asignados (legacy) hacia planes asignados (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de planes asignados...");

        Ajuste::chunk(500, function ($pacientes) {

            $user = User::first();
            foreach ($pacientes as $p) {
                if ($p->estado == 1) {
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
                            'date_start' => $this->parseDateInt($p->fecha_ciclo_insertada),
                            'date_end' => $this->parseDateInt($p->fecha_expiracion),
                            'plan_name' => Plan::find($p->plan_id)->name ?? 'Plan ' . $this->generateRandomCode(AssignedPlan::class, 8, 'plan_name'),
                            'paid_type' => 1,
                            'amount' => $p->costo,
                            'therapies_number' => $p->terapias_fisicas,
                            'number_installments' => Plan::find($p->plan_id)->number_installments ?? 0,
                            'status' => $p->estado,
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
                    $total_items = $assignedPlan->plan->total_sessions;
                    $item_price = $total_items != 0 ? $assignedPlan->amount / $total_items : 0;

                    // Calcular cuántos vouchers necesitamos crear basado en el consumo total
                    $total_consumed_items = (int) $p->sesiones_utilizadas + (int) $p->terapias_utilizadas;

                    // Crear vouchers individuales para que el cálculo de consumo sea correcto
                    if ($total_consumed_items != 0) {

                        for ($i = 0; $i < $total_consumed_items; $i++) {
                            Voucher::create([
                                'assigned_plan_id' => $assignedPlan->id,
                                'status' => 3,
                                'quantity' => 1,
                                'price' => $item_price,
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
