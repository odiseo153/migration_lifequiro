<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\AssignedPlan;
use App\Models\DescuentAuthorization;
use App\Models\Legacy\Autorizaciones;

class MigrateAutorizaciones extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:autorizaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde autorizaciones (legacy) hacia autorizaciones (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de autorizaciones...");

        Autorizaciones::limit(1)->chunk(500, function ($pacientes) {
            $patient_id = Patient::first()->id;
            $assigned_plan_id = AssignedPlan::first()->id;
            $user_id = User::first()->id;

            foreach ($pacientes as $p) {
                DescuentAuthorization::create([
                    'id' => $p->id,
                    'patient_id' => $patient_id,
                    'type' => $p->tipo,
                    'assigned_plan_id' => $assigned_plan_id,
                    'request_amount' => $p->descuento_solicitado,
                    'approved_amount' => $p->descuento_autorizado,
                    'status' => $p->estado,
                    'comment' => $p->nota,
                    'request_by' => $user_id,
                    'authorized_by' => $user_id,
                    'authorized_at' => $p->fecha_aprobacion,
                    'created_at' => $p->fecha,
                    'updated_at' => $p->fecha,
                ]);
            }
        });

        $this->info("Migración completada.");
    }
}
