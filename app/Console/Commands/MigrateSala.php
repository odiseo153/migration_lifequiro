<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\Legacy\Sala;


class MigrateSala extends BaseCommand
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

        Sala::chunk(500, function ($usuarios) {
            foreach ($usuarios as $u) {
                Room::updateOrCreate([
                    'id' => $u->id,
                ], [
                    'name'  => mb_convert_encoding($u->nombre, 'UTF-8', 'auto'),
                ]);
            }
        });

        $this->info("Migración completada.");
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

