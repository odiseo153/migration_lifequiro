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
use App\Models\Legacy\HistorialTerapia;
use App\Models\PhysicalTherapyCategory;
use App\Models\MedicalTerapiaTracionModule;

class MigrateHistorialTerapiaFisicas extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:historial-terapia-fisicas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde historial terapia fisica (legacy) hacia historial terapia fisica (nuevo)';

    /**
     * Execute the console command.
     */
    public $itemsMatch = [
        //['id' => '1', 'name' => 'Masoterapia (Masaje descontracturantes)'],
        //['id' => '2', 'name' => 'Liberación Miofacial'],
        //['id' => '3', 'name' => 'Estiramiento MsSs'],
        //['id' => '4', 'name' => 'Estiramiento Msis'],
        //['id' => '5', 'name' => 'Estiramiento Cervical'],
        //['id' => '6', 'name' => 'Estiramiento Tronco'],
        //['id' => '8', 'name' => 'Ejercicios estabilizadores BackProject'],
        //['id' => '9', 'name' => 'Ejercicios pasivos'],
        // ['id' => '10', 'name' => 'Ejercicios pasivos MsSs'],
        // ['id' => '11', 'name' => 'Ejercicios pasivos Msis'],
        // ['id' => '12', 'name' => 'Ejercicios pasivos Cervical'],
        // ['id' => '13', 'name' => 'Ejercicios pasivos Tronco'],
        // ['id' => '14', 'name' => 'Ejercicios activos MsSs'],
        // ['id' => '15', 'name' => 'Ejercicios activos asistidos Msis'],
        // ['id' => '16', 'name' => 'Ejercicios activos asistidos Cervical'],
        //  ['id' => '17', 'name' => 'Ejercicios activos asistidos Tronco'],
        //  ['id' => '18', 'name' => 'Tracción x10 Mni. Cervical'],
        //  ['id' => '19', 'name' => 'Tracción x10 Mni. Lumbar'],
        // ['id' => '20', 'name' => 'Ejercicios de Fortalecimiento MsSs'],
        //  ['id' => '21', 'name' => 'Ejercicios de Fortalecimiento Msls'],
        // ['id' => '22', 'name' => 'Ejercicios de Fortalecimiento Cervical'],
//['id' => '23', 'name' => 'Ejercicios de Fortalecimiento Tronco']
    ];
//Msss



    public function handle()
    {
        $this->info("Iniciando migración de historial terapia fisica...");

         $itemsMatchTerapiaFisica = [
            1 => PhysicalTherapyCategory::find(48)->id,
            2 => PhysicalTherapyCategory::find(51)->id,
            3 => PhysicalTherapyCategory::find(65)->id,
            4 => PhysicalTherapyCategory::find(69)->id,
            5 => PhysicalTherapyCategory::find(92)->id,
            6 => PhysicalTherapyCategory::find(92)->id,
            8 => PhysicalTherapyCategory::find(60)->id,
            9 => PhysicalTherapyCategory::find(891)->id,
            10 => PhysicalTherapyCategory::find(891)->id,
            11 => PhysicalTherapyCategory::find(891)->id,
            12 => PhysicalTherapyCategory::find(871)->id,
            13 => PhysicalTherapyCategory::find(871)->id,
            14 => PhysicalTherapyCategory::find( 947)->id,
            15 => PhysicalTherapyCategory::find(812)->id,
            16 => PhysicalTherapyCategory::find(793)->id,
            17 => PhysicalTherapyCategory::find(793)->id,
            18 => PhysicalTherapyCategory::find(91)->id,
            19 => PhysicalTherapyCategory::find(91)->id,
            20 => PhysicalTherapyCategory::find(897)->id,
            21 => PhysicalTherapyCategory::find(898)->id,
            22 => PhysicalTherapyCategory::find(898)->id,
            23 => PhysicalTherapyCategory::find(898)->id,
        ];



        HistorialTerapia::chunk(500, function ($historiales) use ($itemsMatchTerapiaFisica) {
            DB::transaction(function () use ($historiales,$itemsMatchTerapiaFisica) {
                foreach ($historiales as $historial) {

                    if (!Patient::find($historial->paciente_id)) {
                        $this->warn("Paciente no encontrado - ID: {$historial->paciente_id}. Omitiendo registro.");
                        continue;
                    }

                    $user = User::find($historial->user_id);

                    if (!$user) {
                        $this->warn("Usuario no encontrado - ID: {$historial->user_id}. Omitiendo registro.");
                        continue;
                    }

                    $item = Item::where('type_of_item_id', ItemType::TERAPIA_FISICA->value)->first();

                    if (!$item) {
                        $item = Item::factory()->create([
                            'type_of_item_id' => ItemType::TERAPIA_FISICA->value,
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
                        'created_at' => $historial->fecha,
                    ]);

                    $room = Room::inRandomOrder()->first() ?? Room::factory()->create();
                    $bed = Bed::inRandomOrder()->first() ?? Bed::factory()->create();

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

                    $categories = explode(',', $historial->tipo_terapia);


                    $medicalTerapiaTracionModule = MedicalTerapiaTracionModule::updateOrCreate(
                        [
                            'id' => $historial->id,
                        ],
                        [
                            'id' => $historial->id,
                            'patient_id' => $historial->paciente_id,
                            'service_id' => $service->id,
                            'acquired_service_id' => $acquiredService->id,
                            'created_at' => $historial->fecha,
                        ]
                    );

                    foreach ($categories as $category) {
                        $medicalTerapiaTracionModule->physical_therapy_category()->attach($itemsMatchTerapiaFisica[$category]);
                    }

                }
            });
        });

        $this->info("Migración completada de historial terapia fisica.");

    }
}
