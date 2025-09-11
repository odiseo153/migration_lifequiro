<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MedicalTerapiaTracionModule;

class DeleteEntity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-entity';

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
MedicalTerapiaTracionModule::all()->delete();
    }
}
