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

        Centro::chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                Branch::create([
                    'id'  => $p->id,
                    'name'  => mb_convert_encoding($p->nombre, 'UTF-8', 'auto'),
                    'company_id'   => 1,
                    'phone'  => $p->telefono,
                    'code' => $p->codigo,
                    'address' => mb_convert_encoding($p->direccion, 'UTF-8', 'auto'),
                ]);
            }
        });

        $this->info("Migración completada.");
    }
}
