<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Legacy\AntecedenteZonasDolor;
use App\Models\MedicalRecord;
use App\Models\Legacy\Antecedente;
use App\Models\Patient;

class MigrateAntecedentes extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:antecedentes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde antecedentes (legacy) hacia antecedentes (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de De antecedentes...");

        AntecedenteZonasDolor::chunk(100, function ($centros) {
            foreach ($centros as $centro) {

                $patient = Patient::find($centro->paciente_id);
                if (!$patient) {
                    $this->warn("Paciente no encontrado - ID: {$centro->paciente_id}. Omitiendo registro.");
                    continue;
                }

                $tipoMap = [
                    1 => "verde",
                    2 => "rojo",
                ];

                MedicalRecord::updateOrCreate([
                    'patient_id' => $patient->id,
                    'id' => $centro->id,
                ], [
                    'id' => $centro->id,
                    'patient_id' => $patient->id,
                    'pain_areas' => json_encode([
                        'type' => $tipoMap[$centro->type],
                        'x' => (int) $centro->leftpos,
                        'y' => (int) $centro->toppos,
                    ]),
                    'consultation_reason' => "",
                    'medical_history' => "",
                    'symptoms_impact_on_life' => "",
                    'current_medication' => "",
                ]);
            }
        });

        $this->info("Migración de De antecedentes completada.");
    }
}
