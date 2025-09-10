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

class MigrateHistorialAjuste extends Command
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

        HistorialAjuste::chunk(500, function ($historiales) {
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
                        'quantity' => 1,
                        'price' => $item->price,
                        'total' => $item->price,
                    ]);

                    $room = Room::first() ?? Room::factory()->create();
                    $bed = Bed::first() ?? Bed::factory()->create();
                    $user = User::first() ?? User::factory()->create();

                    $service->waiting_room()->create([
                        'patient_id' => $historial->paciente_id,
                        'room_id' => $room->id,
                        'bed_id' => $bed->id,
                        'user_id' => $user->id,
                    ]);

                    $acquiredService = AcquiredService::create([
                        'patient_id' => $historial->paciente_id,
                        'price' => $item->price,
                        'status' => ServicesStatus::COMPLETADA->value,
                        'patient_item_id' => $service->id,
                    ]);


                    // Parse zonas field to separate vertebrae by type
                    $cervicalVertebrae = [];
                    $thoracicVertebrae = [];
                    $lumbarVertebrae = [];

                    if (!empty($historial->zonas)) {
                        $zonas = explode(',', $historial->zonas);
                        foreach ($zonas as $zona) {
                            $zona = trim($zona);
                            if (preg_match('/^c\d+/i', $zona)) {
                                $cervicalVertebrae[] = $zona;
                            } elseif (preg_match('/^d\d+/i', $zona)) {
                                $thoracicVertebrae[] = $zona;
                            } elseif (preg_match('/^l\d+/i', $zona)) {
                                $lumbarVertebrae[] = $zona;
                            } else {
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
                            'note' => $historial->note,
                        ]
                    );
                }
            });
        });
    }
}
