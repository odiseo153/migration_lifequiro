<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Position;
use App\Models\Legacy\Usuario;
use Illuminate\Support\Facades\Hash;

class MigrateUsuario extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:usuarios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde usuarios (legacy) hacia usuarios (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de usuarios...");

        Usuario::chunk(500, function ($usuarios) {
            foreach ($usuarios as $u) {
                User::updateOrCreate([
                    'id' => $u->id,
                ], [
                    'id'  => $u->id,
                    'first_name'  => mb_convert_encoding($u->nombre, 'UTF-8', 'auto'),
                    'last_name'  => mb_convert_encoding($u->apellido, 'UTF-8', 'auto'),
                    'email' => $this->randomEmail($u->nombre),
                    'username' => $this->randomUsername($u->nombre),
                    'password' => Hash::make('12345678'),
                    'position_id' => Position::find($u->puesto)?->id ?? 1,
                ]);
            }
        });

        $this->info("Migración completada de usuarios completa.");
    }

    public function randomEmail($nombre)
    {
        return $nombre.rand(1000, 9999).'@example.com';
    }

    public function randomUsername($nombre)
    {
        return $nombre.rand(1000, 9999);
    }
}

