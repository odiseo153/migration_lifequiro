<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\PatientGroup;
use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-centros';

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
    }

    public function parseDateInt($timestamp)
    {
        try {
            // Si está vacío o es null, retornar fecha actual
            if (empty($timestamp) || is_null($timestamp)) {
                return \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            }

            // Si es numérico (número o cadena numérica), tratarlo como timestamp
            if (is_numeric($timestamp)) {
                return \Carbon\Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
            }

            // Si es una cadena que contiene solo números (números en texto), tratarlo como timestamp
            if (is_string($timestamp) && ctype_digit(trim($timestamp))) {
                return \Carbon\Carbon::createFromTimestamp((int)$timestamp)->format('Y-m-d H:i:s');
            }

            // Si es una cadena, intentar parsearlo como fecha
            if (is_string($timestamp)) {
                $parsedDate = \Carbon\Carbon::parse($timestamp);
                return $parsedDate->format('Y-m-d H:i:s');
            }

            // Si no es ninguno de los casos anteriores, retornar fecha actual
            return \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            // En caso de error, retornar fecha actual
            return \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        }
    }

    public function parseDate($timestamp)
    {
        try {
            // Verificar si está vacío, es null, es una cadena vacía o contiene solo espacios
            if (empty($timestamp) || is_null($timestamp) || trim($timestamp) === '') {
                return now();
            }

            // Verificar si es un timestamp numérico igual a 0
            if (is_numeric($timestamp) && $timestamp == 0) {
                return now();
            }

            // Verificar si es una fecha en formato dd-mm-yyyy
            if (is_string($timestamp) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $timestamp)) {
                return \Carbon\Carbon::createFromFormat('d-m-Y', $timestamp);
            }

            return \Carbon\Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return now();
        }
    }

    public function generateRandomCode($model,$length = 8,$field = 'code')
    {
        $code = '';
        while ($model::where($field, $code)->exists()) {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
        }

        return $code;
    }

    public function createPatientIfDoesntExist($paciente_id)
    {
        $legacyPaciente = \App\Models\Legacy\Paciente::find($paciente_id);
        if ($legacyPaciente) {
            // Reutilizar la lógica de mapeo de MigratePatients
            // NOTA: Si tienes métodos utilitarios en MigratePatients, considera extraerlos a un trait o helper.
            $patientGroups = PatientGroup::all()->keyBy('id');

            $where_met_us_id = null;
            $is_refencia_acceptable = $legacyPaciente->referencia != '--' && $legacyPaciente->referencia != '';

            if ($is_refencia_acceptable) {
                $referencia = strtolower(trim($legacyPaciente->referencia));
                $bestScore = 0;
                $bestMatchId = null;

                foreach (\App\Models\WhereHeMetUs::all() as $match) {
                    similar_text($referencia, strtolower($match->name), $percent);
                    if ($percent > $bestScore) {
                        $bestScore = $percent;
                        $bestMatchId = $match->id;
                    }
                }

                if ($bestScore >= 60) {
                    $where_met_us_id = $bestMatchId;
                }
            }

            $branch_id = $legacyPaciente->centro_id == 0 || $legacyPaciente->centro_id == null ? 1 : $legacyPaciente->centro_id;

$document_exists = Patient::where('identity_document', $legacyPaciente->cedula_no)->exists();

            if ($document_exists) {
                return null;
            }

            $patient = Patient::create([
                'id' => $paciente_id,
                'email' => $legacyPaciente->correo,
                'identity_document' => $legacyPaciente->cedula_no == '' ? null : $legacyPaciente->cedula_no,
                'first_name' => $legacyPaciente->nombre ?? "",
                'last_name' => $legacyPaciente->apellido ?? "sin apellido",
                'birth_date' => $this->parseDate($legacyPaciente->fecha_nacimiento),
                'mobile' => $legacyPaciente->celular ?? "",
                'phone' => $legacyPaciente->telefono ?? "",
                'token' => rand(1000, 9999),
                'gender' => $this->mapSexo($legacyPaciente->sexo),
                'civil_status' => $legacyPaciente->estado_civil,
                'address' => $legacyPaciente->direccion ?? "",
                'occupation' => $legacyPaciente->ocupacion ?? "",
                'comment' => $legacyPaciente->comentario ?? "",
                'branch_id' => $branch_id,
                'patient_group_id' => $patientGroups->has($legacyPaciente->grupo) ? $legacyPaciente->grupo : 1,
                'where_met_us_id' => $where_met_us_id ?? 1,
                'created_at' => $legacyPaciente->fecha == null ? now() : $this->parseDateInt($legacyPaciente->fecha),
                'updated_at' => now(),
            ]);

            return $patient;
        }

        return null;
    }

    private function mapSexo($sexo)
    {
        return match (strtolower($sexo)) {
            'masculino', 'm' => 'M',
            'femenino', 'f' => 'F',
            default => null,
        };
    }
}
