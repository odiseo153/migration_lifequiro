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
            if (empty($timestamp) || !is_numeric($timestamp)) {
                return null;
            }
            return \Carbon\Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
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
