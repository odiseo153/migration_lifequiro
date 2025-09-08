<?php
namespace App\Console\Commands;

use App\Models\BranchUser;
use App\Models\Legacy\UsuariosCentro;

class MigrateUsuariosCentros extends BaseCommand
{
    protected $signature = 'migrate:usuarios-centros';
    protected $description = 'Migrar datos desde planes asignados (legacy) hacia planes asignados (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de usuarios centros...");

        UsuariosCentro::limit(2)->chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                BranchUser::create([
                    'id' => $p->id,
                    'user_id' => $p->usuario_id,
                    'branch_id' => $p->centro_id,
                ]);

            }
        });

        $this->info("Migración completada.");
    }



}
