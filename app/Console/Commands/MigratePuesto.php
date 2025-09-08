<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Position;
use App\Models\Legacy\Puesto;
use App\Models\Legacy\Usuario;

class MigratePuesto extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:puestos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde puestos (legacy) hacia puestos (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de puestos...");

        Puesto::chunk(500, function ($puestos) {
            foreach ($puestos as $p) {
                Position::updateOrCreate([
                    'id' => $p->id,
                ], [
                    'name'  => mb_convert_encoding($p->puesto, 'UTF-8', 'auto'),
                ]);
            }
        });

        $this->info("Migración completada.");
    }


}

