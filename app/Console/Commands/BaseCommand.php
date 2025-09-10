<?php

namespace App\Console\Commands;

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

            // Si es numérico, tratarlo como timestamp
            if (is_numeric($timestamp)) {
                return \Carbon\Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
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
                return null;
            }

            // Verificar si es un timestamp numérico igual a 0
            if (is_numeric($timestamp) && $timestamp == 0) {
                return null;
            }

            return \Carbon\Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return null;
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
}
