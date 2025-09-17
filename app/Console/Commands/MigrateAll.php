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
       // $this->call('migrate:usuarios');
      //  $this->call('migrate:usuarios-centros');
        $this->call('migrate:patients');//
       // $this->call('migrate:planes');
        $this->call('migrate:planes-asignados');//
       // $this->call('migrate:historial-llamadas');//
        $this->call('migrate:citas-programadas');//
        $this->call('migrate:historial-ajuste');//
        $this->call('migrate:historial-terapia-fisica');//
        $this->call('migrate:antecedentes');//
        $this->call('migrate:balance');//
        $this->call('migrate:compras');//
        $this->info("Migración de todos los datos completada.");
    }
}
