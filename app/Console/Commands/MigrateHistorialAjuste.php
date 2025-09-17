<?php

namespace App\Console\Commands;

use App\Models\Bed;
use App\Models\Item;
use App\Models\Room;
use App\Models\User;
use App\Enums\ItemType;
use App\Models\Patient;
use App\Models\PatientItem;
use App\Enums\ServicesStatus;
use App\Models\AcquiredService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MedicalAjusteModule;
use App\Models\Legacy\HistorialAjuste;

class MigrateHistorialAjuste extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:historial-ajuste';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde historial ajuste (legacy) hacia historial ajuste (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de historial ajuste...");

        $patientIds = Patient::
        where('branch_id',5)
       ->pluck('id')->toArray();

        HistorialAjuste::
        //whereNotIn('id', MedicalAjusteModule::pluck('id')->toArray())
        whereIn('paciente_id', $patientIds)
        ->chunk(500, function ($historiales) {
            DB::transaction(function () use ($historiales) {
                foreach ($historiales as $historial) {

                    if (!Patient::find($historial->paciente_id)) {
                        $this->warn("Paciente no encontrado - ID: {$historial->paciente_id}. Omitiendo registro.");
                        continue;
                    }

                    $item = Item::where('type_of_item_id', ItemType::AJUSTE->value)->first();

                    if (!$item) {
                        $item = Item::factory()->create([
                            'type_of_item_id' => ItemType::AJUSTE->value,
                        ]);
                        continue;
                    }

                    $service = PatientItem::create([
                        'id' => $historial->service_id,
                        'patient_id' => $historial->paciente_id,
                        'item_id' => $item->id,
                        'quantity' => 0,
                        'price' => $item->price,
                        'total' => $item->price,
                        'created_at' => $historial->fecha,
                    ]);

                    $room = Room::inRandomOrder()->first() ?? Room::factory()->create();
                    $bed = Bed::inRandomOrder()->first() ?? Bed::factory()->create();
                    $user = User::inRandomOrder()->first() ?? User::factory()->create();

                    $service->waiting_room()->create([
                        'patient_id' => $historial->paciente_id,
                        'room_id' => $room->id,
                        'bed_id' => $bed->id,
                        'user_id' => $user->id,
                        'created_at' => $historial->fecha,
                    ]);

                        $acquiredService = AcquiredService::create([
                        'patient_id' => $historial->paciente_id,
                        'price' => $item->price,
                        'status' => ServicesStatus::COMPLETADA->value,
                        'patient_item_id' => $service->id,
                        'created_at' => $historial->fecha,
                    ]);


                    // Parse zonas field to separate vertebrae by type
                    $cervicalVertebrae = [];
                    $thoracicVertebrae = [];
                    $lumbarVertebrae = [];

                    if (!empty($historial->zonas)) {
                        $zonas = explode(',', $historial->zonas);
                        foreach ($zonas as $zona) {
                            $zona = trim($zona);

                            // Check for cervical vertebrae (c1, c2, etc)
                            if (preg_match('/^c\d+/i', $zona)) {
                                $cervicalVertebrae[] = $zona;
                                // Add "right" modifier if present
                                if (stripos($zona, 'right') !== false) {
                                    $cervicalVertebrae[] = 'right';
                                }
                            }
                            // Check for thoracic/dorsal vertebrae (d3, d4, etc)
                            elseif (preg_match('/^d\d+/i', $zona)) {
                                $thoracicVertebrae[] = $zona;
                                if (stripos($zona, 'right') !== false) {
                                    $thoracicVertebrae[] = 'right';
                                }
                            }
                            // Check for lumbar vertebrae (l2, l3, etc)
                            elseif (preg_match('/^l\d+/i', $zona)) {
                                $lumbarVertebrae[] = $zona;
                                if (stripos($zona, 'right') !== false) {
                                    $lumbarVertebrae[] = 'right';
                                }
                            }
                            // Handle sacral vertebrae and other modifiers
                            elseif (preg_match('/^s/i', $zona)) {
                                $lumbarVertebrae[] = $zona;
                                if (stripos($zona, 'right') !== false) {
                                    $lumbarVertebrae[] = 'right';
                                }
                            }
                            // Add any remaining modifiers to cervical
                            else {
                                $cervicalVertebrae[] = $zona;
                            }
                        }
                    }

                    MedicalAjusteModule::updateOrCreate(
                        [
                            'id' => $historial->id,
                        ],
                        [
                            'id' => $historial->id,
                            'patient_id' => $historial->paciente_id,
                            'service_id' => $service->id,
                            'acquired_service_id' => $acquiredService->id,
                            'pain_intensity' => $historial->rango_dolor,
                            'cervical_vertebrae' => implode(', ', $cervicalVertebrae),
                            'thoracic_vertebrae' => implode(', ', $thoracicVertebrae),
                            'lumbar_vertebrae' => implode(', ', $lumbarVertebrae),
                            'created_at' => $historial->fecha,
                        ]
                    );
                }
            });
        });

        $this->info("Migración completada de historial ajuste.");

    }
}
