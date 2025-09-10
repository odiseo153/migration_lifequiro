<?php
namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Plan;
use App\Models\User;
use App\Enums\ItemType;
use App\Models\Patient;
use App\Enums\ServicesStatus;
use App\Models\Legacy\Ajuste;
use App\Models\Legacy\Planes;
use App\Models\{AssignedPlan};
use App\Models\AcquiredService;
use App\Models\DescuentAuthorization;

class MigratePlanesAsignados extends BaseCommand
{
    protected $signature = 'migrate:planes-asignados';
    protected $description = 'Migrar datos desde planes asignados (legacy) hacia planes asignados (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de planes asignados...");

        Ajuste::chunk(500, function ($pacientes) {

            $user = User::factory()->create();
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


                    $assignedPlan = AssignedPlan::updateOrCreate(
                        [
                            'id' => $p->id,
                        ],
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

                    $assignedPlan->transactions()->create([
                        'assigned_plan_id' => $assignedPlan->id,
                    'patient_id' => $p->paciente_id,
                    'amount' => $p->consumido,
                    'transaction_type' => 'entrada',
                    'description' => 'Plan asignado',
                ]);
                if ($p->descuento != 0) {
                    for ($i = 0; $i < $p->descuento; $i++) {
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
                }

                $priceAjuste = $p->sessiones_utilizadas > 0 ? $p->consumido / $p->sessiones_utilizadas : 0;
                $priceTerapia = $p->terapia_fisica > 0 ? $p->consumido / $p->terapia_fisica : 0;

                if ($p->sessiones_utilizadas != 0) {
                    for ($i = 0; $i < $p->sessiones_utilizadas; $i++) {
                        AcquiredService::create([
                            'patient_id' => $p->paciente_id,
                            'assigned_plan_id' => $assignedPlan->id,
                            'plan_item_id' => Item::where('plan', true)->where('type_of_item_id', ItemType::AJUSTE->value)->first()->id,
                            'price' => $priceAjuste,
                            'status' => ServicesStatus::COMPLETADA->value,
                        ]);
                    }
                }


                if ($p->terapias_utilizadas != 0) {
                    for ($i = 0; $i < $p->terapias_utilizadas; $i++) {
                        AcquiredService::create([
                            'patient_id' => $p->paciente_id,
                            'assigned_plan_id' => $assignedPlan->id,
                            'plan_item_id' => Item::where('plan', true)->where('type_of_item_id', ItemType::TERAPIA_FISICA->value)->first()->id,
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
