<?php
namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Plan;
use App\Models\User;
use App\Models\Patient;
use App\Models\Legacy\Ajuste;
use App\Models\{AssignedPlan};
use App\Models\AcquiredService;

class MigratePlanesAsignados extends BaseCommand
{
    protected $signature = 'migrate:planes-asignados';
    protected $description = 'Migrar datos desde planes asignados (legacy) hacia planes asignados (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de planes asignados...");

        Ajuste::chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                // Verificar si el paciente existe
                if (!Patient::find($p->paciente_id)) {
                    $this->warn("Paciente no encontrado - ID: {$p->paciente_id}. Omitiendo registro.");
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
                    'date_start' => $p->fecha_ciclo_insertada == '' || $p->fecha_ciclo_insertada == null ? now()->format('Y-m-d') : ($this->parseDateInt($p->fecha_ciclo_insertada) ?? now()->format('Y-m-d')),
                    'date_end' => $p->fecha_expiracion == '' || $p->fecha_expiracion == null ? now()->format('Y-m-d') : ($this->parseDateInt($p->fecha_expiracion) ?? now()->format('Y-m-d')),
                    'plan_name' => Plan::find($p->plan_id)->name ?? 'Plan '.$this->generateRandomCode(AssignedPlan::class,8,'plan_name'),
                    'paid_type' => 1,
                    'amount' => $p->costo,
                    'therapies_number' => $p->terapias_fisicas,
                    'number_installments' => Plan::find($p->plan_id)->number_installments ?? 0,
                    'status' => $p->estado,
                    'branch_id' => $p->centro_id,
                    'user_id' => User::first()->id,
                    'card_commission' => $p->card_fee,
                    'bank_commission' => $p->bank_fee,
                    'other_commission' => $p->other_fee,
                    'created_at' => $this->parseDateInt($p->fecha_cre),
                    'updated_at' => $this->parseDateInt($p->fecha_cre),
                ]);

                $assignedPlan->transactions()->create([
                    'assigned_plan_id' => $assignedPlan->id,
                    'patient_id' => $p->paciente_id,
                    'amount' => $p->consumido,
                    'transaction_type' => 'entrada',
                    'description' => 'Plan asignado',
                ]);

                $priceAjuste = $p->sessiones_utilizadas > 0 ? $p->consumido / $p->sessiones_utilizadas : 0;
                $priceTerapia = $p->terapia_fisica > 0 ? $p->consumido / $p->terapia_fisica : 0;

                if ($p->sessiones_utilizadas) {
                    for ($i = 0; $i < $p->sessiones_utilizadas; $i++) {
                        AcquiredService::create([
                            'patient_id' => $p->paciente_id,
                            'assigned_plan_id' => $assignedPlan->id,
                            'plan_item_id' => Item::where('plan', true)->where('type_of_item_id', 7)->first()->id,
                            'price' => $priceAjuste,
                            'status' => 3,
                        ]);
                    }
                }

                if ($p->terapias_utilizadas) {
                    for ($i = 0; $i < $p->terapias_utilizadas; $i++) {
                        AcquiredService::create([
                            'patient_id' => $p->paciente_id,
                            'assigned_plan_id' => $assignedPlan->id,
                            'plan_item_id' => Item::where('plan', true)->where('type_of_item_id', 5)->first()->id,
                            'price' => $priceTerapia,
                            'status' => 3,
                        ]);
                    }
                }
            }
        });

        $this->info("Migración completada.");
    }



}
