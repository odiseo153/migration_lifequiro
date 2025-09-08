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

        Centro::chunk(100, function ($centros) {
            foreach ($centros as $centro) {
                Branch::updateOrCreate([
                    'id'  => $centro->id,
                ], [
                    'id'  => $centro->id,
                    'name'  => mb_convert_encoding($centro->nombre, 'UTF-8', 'auto'),
                    'company_id'   => 8, // company_id 8 es la empresa de Quirocita
                    'phone'  => $centro->telefono,
                    'code' => $centro->codigo,
                    'address' => mb_convert_encoding($centro->direccion, 'UTF-8', 'auto'),
                ]);
            }
        });

        $this->info("Migración de centros completada.");
    }
}
