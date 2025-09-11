<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eliminar datos de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
       //// $this->call('delete:centros');
        $this->call('delete:patients');
        $this->call('delete:planes');
        $this->call('delete:planes-asignados');
    }
}
