<?php

namespace App\Console\Commands;

use App\Models\PaymentMethod;
use Illuminate\Console\Command;
use App\Models\Legacy\FormaPago;

class MigrateFormaPago extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:forma-pago';

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
        $this->info("Iniciando migración de formas de pago...");

        FormaPago::chunk(500, function ($formaPagos) {
            foreach ($formaPagos as $fp) {
                if ($fp->id != 4) {
                    PaymentMethod::updateOrCreate(
                        [
                            'id' => $fp->id,
                        ],
                        [
                            'id' => $fp->id,
                            'name' => $fp->forma_pago,
                        ]
                    );
                }
            }
        });

        $this->info("Migración de formas de pago completada.");

    }
}
