<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Enums\ItemType;
use App\Models\Patient;
use App\Models\PatientItem;
use App\Models\Legacy\Compra;

class MigrateCompras extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:compras';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde compras (legacy) hacia compras (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de compras...");
        $comprasTipo = [
            1 => ItemType::CONSULTA->value,
            2 => ItemType::RADIOGRAFIA->value,
            3 => ItemType::COMPARACION->value,
            4 => ItemType::REPORTE->value,
            5 => ItemType::AJUSTE->value,
            6 => ItemType::AJUSTE->value,
            7 => ItemType::TERAPIA_FISICA->value,
            8 => ItemType::TRACCION->value,
            9 => ItemType::TERAPIA_FISICA->value,
        ];
        $patientIds = Patient::
        where('branch_id',5)
       ->pluck('id')->toArray();

        \DB::transaction(function() use ($comprasTipo, $patientIds) {
            Compra::
            where('estado', 1)
            ->where('tipo_servicio','!=', 0)
            ->whereIn('paciente_id', $patientIds)
                ->chunk(500, function ($pacientes) use ($comprasTipo) {
                    foreach ($pacientes as $p) {
                        if (!Patient::find($p->paciente_id)) {
                            $this->warn("Paciente no encontrado - ID: {$p->paciente_id}. Omitiendo registro.");
                            continue;
                        }

                        $item = Item::where('type_of_item_id', $comprasTipo[$p->tipo_servicio])->first();
                        if (!$item) {
                            $item = Item::factory()->create([
                                'type_of_item_id' => $comprasTipo[$p->tipo_servicio],
                            ]);
                        }

                        PatientItem::updateOrCreate(
                            [
                                'id' => $p->id,
                            ],
                            [
                                'id' => $p->id,
                                'patient_id' => $p->paciente_id,
                                'item_id' => $item->id,
                                'description' => $p->servicio,
                                'quantity' => 1,
                                'total' => $p->costo,
                                'created_at' => $p->fecha_comprado,
                            ]
                        );
                    }
                });
        });

        $this->info("Migración de comprascompletada.");
    }
}
