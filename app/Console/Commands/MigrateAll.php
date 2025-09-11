<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


class MigrateAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:all';

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
        //
$this->info("Iniciando migración de todos los datos...");
        $this->call('migrate:patients');
        $this->info("Iniciando migración de planes...");
        $this->call('migrate:planes-asignados');
        $this->info("Iniciando migración de historial llamadas...");
        $this->call('migrate:historial-llamadas');
        $this->info("Iniciando migración de historial ajuste...");
        $this->call('migrate:historial-ajuste');
        $this->info("Iniciando migration de historial terapia fisica...");
        $this->call('migrate:historial-terapia-fisica');
        //$this->call('migrate:planes');
        //$this->call('migrate:planes-asignados');
        $this->info("Migración de todos los datos completada.");
    }
}
