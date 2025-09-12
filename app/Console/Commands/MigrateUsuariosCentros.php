<?php
namespace App\Console\Commands;

use App\Models\User;
use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\Legacy\UsuariosCentro;

class MigrateUsuariosCentros extends BaseCommand
{
    protected $signature = 'migrate:usuarios-centros';
    protected $description = 'Migrar datos desde planes asignados (legacy) hacia planes asignados (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de usuarios centros...");

        UsuariosCentro::chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                $user = User::find($p->usuario_id);

                if (!$user) {
                    $this->warn("Usuario no encontrado - ID: {$p->usuario_id}. Omitiendo registro.");
                    continue;
                }

                $branch = Branch::find($p->centro_id);

                if (!$branch) {
                    $this->warn("Centro no encontrado - ID: {$p->centro_id}. Omitiendo registro.");
                    continue;
                }

                BranchUser::updateOrCreate([
                    'id' => $p->id,
                ], [
                    'id' => $p->id,
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                ]);

            }
        });

        $this->info("Migración completada.");
    }



}
