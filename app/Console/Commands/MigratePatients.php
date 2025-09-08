<?php
    namespace App\Console\Commands;

use App\Models\Legacy\Paciente;
use App\Models\Patient;

class MigratePatients extends BaseCommand
{
    protected $signature = 'migrate:patients';
    protected $description = 'Migrar datos desde paciente (legacy) hacia patients (nuevo)';

    public function handle()
    {
        $this->info("Iniciando migración de pacientes...");

        Paciente::chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                try {
                    Patient::updateOrCreate(
                        [
                            'id'  => $p->id,
                        ],
                        [
                            'id'  => $p->id,
                            'email'  => $p->correo,
                            'identity_document' => $p->cedula_no=='' ? null : $p->cedula_no,
                            'first_name'  => $p->nombre ?? "",
                            'last_name'  => $p->apellido ?? "sin apellido",
                            'birth_date'  =>$this->parseDate($p->fecha_nacimiento) ? $this->parseDate($p->fecha_nacimiento) : now(),
                            'mobile'  => $p->celular ?? "",
                            'phone'  => $p->telefono ?? "",
                            'token'       => rand(1000, 9999),
                            'gender'      => $this->mapSexo($p->sexo),
                            'civil_status'=> $p->estado_civil,
                            'address'  => $p->direccion ?? "",
                            'occupation'  => $p->ocupacion ?? "",
                            'comment'  => $p->comentario ?? "",
                            'branch_id'   => $p->centro_id ==0 || $p->centro_id == null ? 1 : $p->centro_id, // por defecto
                            //'patient_group_id' => $p->grupo ?? null,
                        ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062) { // Duplicate entry error
                        $this->warn("Paciente duplicado encontrado - ID: {$p->id}, Cédula: {$p->cedula_no}");
                        continue;
                    }
                    throw $e;
                }
            }
        });

        $this->info("Migración de pacientes completada.");
    }

    private function parseDate($fecha)
    {
        try {
            if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
                return null;
            }

            $parsed = \Carbon\Carbon::parse($fecha);

            // Check if the year is valid (not negative or too old)
            if ($parsed->year < 1900) {
                return null;
            }

            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mapSexo($sexo)
    {
        return match(strtolower($sexo)) {
            'masculino', 'm' => 'M',
            'femenino', 'f' => 'F',
            default => null,
        };
    }


}
