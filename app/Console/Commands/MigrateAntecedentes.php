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
            // Agrupar por paciente_id
            $centrosByPatient = $centros->groupBy('paciente_id');

            foreach ($centrosByPatient as $pacienteId => $centrosDelPaciente) {
                $patient = Patient::find($pacienteId);
                if (!$patient) {
                    $this->warn("Paciente no encontrado - ID: {$pacienteId}. Omitiendo registro.");
                    continue;
                }

                // Verificar si ya existe un registro médico para este paciente
                if (MedicalRecord::where('patient_id', $patient->id)->exists()) {
                    $this->warn("Paciente ya tiene registro médico - ID: {$pacienteId}. Omitiendo registro.");
                    continue;
                }

                $tipoMap = [
                    1 => "verde",
                    2 => "rojo",
                ];

                $painAreas = [];
                foreach ($centrosDelPaciente as $centro) {
                    $painAreas[] = [
                        'type' => $tipoMap[$centro->type],
                        'x' => (int) $centro->leftpos,
                        'y' => (int) $centro->toppos,
                    ];
                }

                MedicalRecord::create([
                    'patient_id' => $patient->id,
                    'pain_areas' => json_encode($painAreas),
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
