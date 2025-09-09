<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    Bed,
    PaymentMethod,
    TypeOfTaxReceipt,
    HistoryMedical,
    //  PatientDocument,
    GroupWhereMetUs,
    Item,
    DiagnosisAndTreatment,
    NeurologicalAndFunctionalEvaluation,
    Plan,
    Room,
    TypeOfItem,
    Offer,
    Branch,
    Doctor,
    Patient,
    TypeOfPlan,
    TypeOfAppointments,
    Appointment,
    WhereHeMetUs,
    EmergencyContact,
    PatientMedicalData,
    MedicalRecord,
    Status,
    PhysicalExamination,
    Ars,
    TransactionType
};
use Database\Seeders\{
    UserSeeder,
    RolePermissionSeeder,
    ModulePermissionSeeder
};

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Iniciando la creación de datos iniciales...');



        $statuses = [
            ['state' => 'Completada', 'order' => 4, 'id' => 1],
            ['state' => 'Programada', 'order' => 1, 'id' => 2],
            ['state' => 'Pospuesta', 'order' => 5, 'id' => 3],
            ['state' => 'No Asistió', 'order' => 6, 'id' => 4],
            ['state' => 'Atendiendo', 'order' => 3, 'id' => 6],
            ['state' => 'En Espera', 'order' => 2, 'id' => 7],
            ['state' => 'Radiografía', 'order' => 8, 'id' => 8],
            ['state' => 'Reprogramada', 'order' => 14, 'id' => 15],
            ['state' => 'No Radiografía', 'order' => 15, 'id' => 16],
            ['state' => 'Confirmada', 'order' => 17, 'id' => 18],
            ['state' => 'Desactivada', 'order' => 18, 'id' => 19]
        ];
        Status::insert($statuses);
        $this->command->info('- Estados (statuses) creados con éxito.');



        // Crear Tipos de Citas
        $typesOfAppointments = [
            ['name' => 'Consulta', 'id' => 1],
            ['name' => 'Radiografía', 'id' => 2],
            ['name' => 'Reporte', 'id' => 3],
            ['name' => 'MIP', 'id' => 4],
            ['name' => 'MR', 'id' => 5],
            ['name' => 'Analisis de Postura', 'id' => 6],
            ['name' => 'Comparacion', 'id' => 7],
            ['name' => 'Radiografía RC', 'id' => 8],
        ];
        TypeOfAppointments::insert($typesOfAppointments);
        $this->command->info('- Tipos de citas creados con éxito.');


        /*

        // Crear Tipos de Planes
        $typesOfPlans = [
            ['name' => 'Regular', 'id' => 1],
            ['name' => 'VIP', 'id' => 2],
        ];
        foreach ($typesOfPlans as $type) {
            TypeOfPlan::updateOrCreate(['id' => $type['id']], ['name' => $type['name'], 'id' => $type['id']]);
        }
        $this->command->info('- Tipos de planes creados con éxito.');

        $paymentMethods = [
            ['name' => 'Efectivo', 'id' => 1],
            ['name' => 'Tarjeta Credito / Debito', 'id' => 2],
            ['name' => 'Cheque', 'id' => 3],
            ['name' => 'Transferencia o Deposito', 'id' => 4],
            ['name' => 'Nota de Credito', 'id' => 5],
            ['name' => 'Credito', 'id' => 6],
            //  ['name' => 'Saldo a favor', 'id' => 6],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(['id' => $method['id']], ['name' => $method['name'], 'id' => $method['id']]);
        }
        $this->command->info('- Métodos de pago creados con éxito.');

        $transationTypes = [
            ['name' => 'Items', 'id' => 1],
            ['name' => 'Combos Ofertas', 'id' => 2],
            ['name' => 'Pago Plan', 'id' => 3],
            ['name' => 'Nota de Credito', 'id' => 4],
            ['name' => 'Pago Deuda', 'id' => 5],
        ];

        foreach ($transationTypes as $type) {
            TransactionType::updateOrCreate(['id' => $type['id']], ['name' => $type['name'], 'id' => $type['id']]);
        }
        $this->command->info('- Tipos de transacciones creados con éxito.');


        $typesOfTaxReceipts = [
            ['name' => 'Comprobante Fiscal', 'id' => 1],
        ];

        foreach ($typesOfTaxReceipts as $type) {
            TypeOfTaxReceipt::updateOrCreate(['id' => $type['id']], ['name' => $type['name'], 'id' => $type['id']]);
        }
        $this->command->info('- Tipos de comprobantes fiscales creados con éxito.');


        $this->command->info('- Seeders de roles, permisos y usuarios ejecutados.');

        // Crear Statuses
        $statuses = [
                    ['state' => 'Completada', 'order' => 4, 'id' => 1],
                    ['state' => 'Programada', 'order' => 1, 'id' => 2],
                    ['state' => 'Pospuesta', 'order' => 5, 'id' => 3],
                    ['state' => 'No Asistió', 'order' => 6, 'id' => 4],
                    ['state' => 'Atendiendo', 'order' => 3, 'id' => 6],
                    ['state' => 'En Espera', 'order' => 2, 'id' => 7],
                    ['state' => 'Radiografía', 'order' => 8, 'id' => 8],
                    ['state' => 'Reprogramada', 'order' => 14, 'id' => 15],
                    ['state' => 'No Radiografía', 'order' => 15, 'id' => 16],
                    ['state' => 'Confirmada', 'order' => 17, 'id' => 18],
                    ['state' => 'Desactivada', 'order' => 18, 'id' => 19]
                ];
                Status::insert($statuses);
                $this->command->info('- Estados (statuses) creados con éxito.');



                // Crear Tipos de Citas
                $typesOfAppointments = [
                    ['name' => 'Consulta'],
                    ['name' => 'Radiografía'],
                    ['name' => 'Reporte'],
                    ['name' => 'MIP'],
                    ['name' => 'MR'],
                    ['name' => 'Analisis de Postura'],
                    ['name' => 'Radiografía RC']
                ];
                TypeOfAppointments::insert($typesOfAppointments);
                $this->command->info('- Tipos de citas creados con éxito.');

                // Crear Tipos de Servicios
                $types = [
                    ['name' => 'Consulta', 'id' => 1],
                    ['name' => 'Radiografía', 'id' => 2],
                    ['name' => 'Reporte', 'id' => 3],
                    ['name' => 'Comparación', 'id' => 4],
                    ['name' => 'Terapia Física', 'id' => 5],
                    ['name' => 'Tracción', 'id' => 6],
                    ['name' => 'Ajuste', 'id' => 7],
                    ['name' => 'Analisis de Postura', 'id' => 8],
                ];
                TypeOfItem::insert($types);
                $this->command->info('- Tipos de servicios creados con éxito.');


                $arsList = [
                    'ARS APS',
                    'ARS Amor y Paz',
                    'ARS ASEMAP',
                    'ARS Asistanet',
                    'ARS Banreservas',
                    'ARS CMD',
                    'ARS Futuro',
                    'ARS GMA',
                    'ARS Humano',
                    'ARS La Monumental',
                    'ARS MAPFRE Salud',
                    'ARS Renacer',
                    'ARS SEMMA',
                    'ARS SeNaSa',
                ];

                Ars::insert($arsList);
                $this->command->info('- ARS creados con éxito.');

                */

        // Crear datos de prueba con factories
        //   $this->createFactoryData();
    }

    /**
     * Crear datos de prueba usando factories.
     */
    private function createFactoryData(): void
    {
        PatientMedicalData::factory(10)->create();
        $this->command->info('- 10 registros de datos médicos de pacientes creados.');

        GroupWhereMetUs::factory(10)->create();
        $this->command->info('- 10 registros en "Dónde nos conoció Grupos" creados.');

        WhereHeMetUs::factory(10)->create();
        $this->command->info('- 10 registros en "Dónde nos conoció" creados.');

        Doctor::factory(10)->create();
        $this->command->info('- 10 doctores creados.');

        EmergencyContact::factory(10)->create();
        $this->command->info('- 10 contactos de emergencia creados.');


        Patient::factory(10)->create();
        $this->command->info('- 10 pacientes creados.');

        // Crear sucursales con horarios
        Branch::factory(10)
            ->hasSchedules(5)
            ->create();
        $this->command->info('- 10 sucursales creadas con horarios asociados.');

        // Crear citas con relaciones
        Appointment::factory(10)->create();
        $this->command->info('- 10 citas creadas con entidades relacionadas.');

        Room::factory(10)
            ->hasBeds(5)
            ->hasBranches(5)
            ->create();
        $this->command->info('- 10 habitaciones creadas con camas y sucursales asociadas.');

        Item::factory(10)->create();
        $this->command->info('- 10 ítems creados.');

        MedicalRecord::factory(10)->create();
        $this->command->info('- 10 registros médicos creados.');

        Offer::factory(10)->
            hasItems(6)
            ->hasBranches(6)
            ->create();
        $this->command->info('- 10 ofertas creadas.');

        Bed::factory(10)->create();
        $this->command->info('- 10 camas creadas.');

        Plan::factory(10)->create();
        $this->command->info('- 10 planes creados.');

        TypeOfTaxReceipt::factory(10)->create();
        $this->command->info('- 10 tipos de comprobantes creados creados.');

        PhysicalExamination::factory(10)->create();
        $this->command->info('- 10 exámenes físicos creados.');

        HistoryMedical::factory(10)->create();
        $this->command->info('- 10 historiales médicos creados.');

        NeurologicalAndFunctionalEvaluation::factory(10)->create();
        $this->command->info('- 10 evaluaciones neurológicas y funcionales creadas.');

        DiagnosisAndTreatment::factory(10)->create();
        $this->command->info('- 10 diagnósticos y tratamientos creados.');


    }
}




