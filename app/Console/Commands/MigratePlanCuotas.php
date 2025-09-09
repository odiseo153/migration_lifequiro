<?php

namespace App\Console\Commands;

use App\Models\Legacy\PlanCuotas;
use App\Models\AssignedPlan;
use Illuminate\Console\Command;
use App\Models\Installment;

class MigratePlanCuotas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:plan-cuotas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de plan cuotas...");

        PlanCuotas::chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                if (!AssignedPlan::find($p->ajuste_id)) {
                    $this->warn("Plan no encontrado - ID: {$p->ajuste_id}. Omitiendo registro.");
                    continue;
                }

                Installment::updateOrCreate([
                    'id' => $p->id,
                ], [
                    'id' => $p->id,
                    'assigned_plan_id' => $p->ajuste_id,
                    'amount' =>(int) $p->monto,
                    'date_paid' => $p->date,
                    'is_it_paid' => $p->status == 2 ? true : false,
                    'created_at' => $p->date,
                ]);
            }
        });

        $this->info("Migración completada de plan cuotas.");

    }
}
