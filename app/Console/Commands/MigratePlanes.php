<?php
    namespace App\Console\Commands;

use App\Models\{Plan};
use App\Models\Legacy\Planes;

class MigratePlanes extends BaseCommand
{
    protected $signature = 'migrate:planes';
    protected $description = 'Migrar datos desde paciente (legacy) hacia patients (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de planes...");

        Planes::chunk(100, function ($pacientes) {
            foreach ($pacientes as $p) {
                Plan::updateOrCreate([
                    'id'  => $p->id,
                ], [
                    'id'  => $p->id,
                    'name'  => $p->plan,
                    'code'   => $p->codigo ?? 'GENERATED-'.$this->generateRandomCode(Plan::class,8,'code'),
                    'price'  => $p->precio ?? 0,
                    'total_sessions' => $p->total_sessiones_plan ?? 0,
                    'type_of_plan_id'       => $p->tipo ==0 ? 1 :$p->tipo ,
                    'therapies_number'       => $p->total_terapia_fisica ?? 0,
                    'number_installments'       => $p->cuotas_pagos ?? 0,
                    'duration' => $p->tiempo ?? 0,
                    'available'  => $p->estado,
                    'created_at' => $this->parseDateInt($p->fecha),
                    'updated_at' => $this->parseDateInt($p->fecha),
                ]);
            }
        });

        $this->info("Migración de planes completada.");
    }



}
