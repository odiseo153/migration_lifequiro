<?php

namespace App\Console\Commands;

use App\Models\AssignedPlan;
use Illuminate\Console\Command;
use App\Models\Legacy\PlanCuotas;
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

        PlanCuotas::limit(2)->chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                Installment::create([
                    'id' => $p->id,
                    'assigned_plan_id' => AssignedPlan::first()->id,
                    'amount' => $p->monto,
                    'date_paid' => $p->fecha_pago,
                    'is_it_paid' => $p->status == 2 ? 1 : 0,
                    'created_at' => $p->date,
                ]);
            }
        });

        $this->info("Migración completada.");

    }
}
