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
MedicalTerapiaTracionModule::all()->each(function ($module) {
    // Delete all relationships
    $module->physical_therapy_category()->detach();
    $module->acquired_service()->delete();
    $module->service()->delete();

    // Delete the module itself
    $module->delete();

    $this->info("Deleted module ID: {$module->id}");
});

$this->info('All MedicalTerapiaTracionModule records and their relationships have been deleted.');
    }
}
