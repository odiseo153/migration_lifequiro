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

        Antecedente::
        whereNotIn('paciente_id', MedicalRecord::pluck('patient_id')->toArray())
        ->chunk(100, function ($antecedentes) {
            foreach ($antecedentes as $antecedente) {
                $patient = Patient::find($antecedente->paciente_id);
                if (!$patient) {
                    $this->warn("Paciente no encontrado - ID: {$antecedente->paciente_id}. Omitiendo registro.");
                    continue;
                }

                MedicalRecord::create([
                    'patient_id' => $patient->id,
                    'id' => $antecedente->id,
                    'consultation_reason' => $antecedente->motivo_consulta,
                    'medical_history' => $antecedente->motivos_visita_medico,
                    'symptoms_impact_on_life' => $antecedente->dano_sintomas,
                    'current_medication' => $antecedente->medicamentos,
                ]);
            }
        });

        $this->info("Migración de De antecedentes completada.");
    }
}
