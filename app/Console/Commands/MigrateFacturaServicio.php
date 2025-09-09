<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Enums\ItemType;
use App\Models\Patient;
use App\Models\TypeOfItem;
use App\Models\AssignedPlan;
use App\Models\PaymentMethod;
use App\Models\Voucher;
use App\Models\Legacy\FacturaServicio;


class MigrateFacturaServicio extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:facturas-servicio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde facturas servicio (legacy) hacia facturas servicio (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de facturas servicio...");

        FacturaServicio::chunk(500, function ($facturas) {
            foreach ($facturas as $f) {
                if (!Patient::find($f->paciente_id)) {
                    $this->warn("Paciente no encontrado - ID: {$f->paciente_id}. Omitiendo registro.");
                    continue;
                }

                Voucher::updateOrCreate([
                    'id' => $f->id,
                ], [
                    'appointment_id' => $f->cita_id,
                    'assigned_plan_id' => $f->ajuste_plan_id != 0 && AssignedPlan::find($f->ajuste_plan_id) ? $f->ajuste_plan_id : null,
                    'status' => $f->status,
                    'quantity' => $f->quantity,
                    'price' => $f->price,
                    'created_at' => $this->parseDateInt($f->fecha),
                ]);


            }
        });

        $this->info("Migración completada de facturas.");
    }


}

