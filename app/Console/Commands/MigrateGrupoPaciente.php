<?php

namespace App\Console\Commands;

use App\Models\PatientGroup;
use Illuminate\Console\Command;
use App\Models\Legacy\GrupoPaciente;

class MigrateGrupoPaciente extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:grupo-paciente';

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
        $this->info("Iniciando migración de grupos de pacientes...");

        GrupoPaciente::chunk(500, function ($grupos) {
            foreach ($grupos as $g) {
                PatientGroup::updateOrCreate(
                    [
                        'id' => $g->id,
                    ],
                    [
                    'id' => $g->id,
                    'name' => $g->nombre,
                    'created_at' => $g->date,
                ]);
            }
        });

        $this->info("Migración de grupos de pacientes completada.");

    }
}
