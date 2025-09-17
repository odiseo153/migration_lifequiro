<?php

namespace App\Console\Commands;

use App\Enums\PaymentMethodType;
use App\Models\CreditNote;
use App\Models\Patient;
use App\Models\Legacy\Balance;

class MigrateBalance extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde balance (legacy) hacia balance (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de balance...");

        Balance::
        where('monto', '>', 0)
        ->where('estado', 1)
        ->whereNotIn('id', CreditNote::pluck('id')->toArray())
        ->chunk(500, function ($pacientes) {
            foreach ($pacientes as $p) {
                if (!Patient::find($p->paciente_id)) {
                    $this->warn("Paciente no encontrado - ID: {$p->paciente_id}. Omitiendo registro.");
                    continue;
                }

                CreditNote::create(
       [
                    'id' => $p->id,
                    'patient_id' => $p->paciente_id,
                    'amount' => $p->monto,
                    'payment_method_id' => PaymentMethodType::NOTA_CREDITO->value,
                    'note' => "Balance de paciente migrado",
                ]);
            }
        });

        $this->info("Migración completada.");
    }
}
