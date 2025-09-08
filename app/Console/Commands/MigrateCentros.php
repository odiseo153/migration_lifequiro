<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Legacy\Centro;

class MigrateCentros extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:centros';

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
        $this->info("Iniciando migración de centros...");

        Centro::chunk(100, function ($pacientes) {
            foreach ($pacientes as $p) {
                Branch::updateOrCreate([
                    'id'  => $p->id,
                ], [
                    'id'  => $p->id,
                    'name'  => mb_convert_encoding($p->nombre, 'UTF-8', 'auto'),
                    'company_id'   => 8, // company_id 8 es la empresa de Quirocita
                    'phone'  => $p->telefono,
                    'code' => $p->codigo,
                    'address' => mb_convert_encoding($p->direccion, 'UTF-8', 'auto'),
                ]);
            }
        });

        $this->info("Migración de centros completada.");
    }
}
