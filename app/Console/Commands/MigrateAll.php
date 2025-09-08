<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Console\Commands\MigrateCentros;
use App\Console\Commands\MigratePatients;
use App\Console\Commands\MigratePlanes;
use App\Console\Commands\MigratePlanesAsignados;

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
        $this->call('migrate:centros');
        $this->call('migrate:patients');
        $this->call('migrate:planes');
        $this->call('migrate:planes-asignados');
    }
}
