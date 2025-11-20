<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class RemigrateBranches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:remigrate-branches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-migra los datos de los pacientes de las branches especificadas en BRANCHS_TO_MIGRATE';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $branches = Patient::BRANCHS_TO_MIGRATE;

        $this->info("Iniciando re-migración para las branches: " . implode(', ', $branches));

        // Obtener IDs de pacientes de las branches especificadas
        $patientIds = Patient::whereIn('branch_id', $branches)->pluck('id')->toArray();

        if (empty($patientIds)) {
            $this->warn("No se encontraron pacientes en las branches especificadas.");
            return;
        }

        $this->info("Se encontraron " . count($patientIds) . " pacientes para re-migrar.");

        if (!$this->confirm('¿Estás seguro de que deseas eliminar todos los datos relacionados y re-migrar?')) {
            $this->info('Operación cancelada.');
            return;
        }

        $this->info("Eliminando datos relacionados...");

        DB::beginTransaction();

        try {
            // 1. Eliminar planes asignados y sus relaciones
            $this->info("- Eliminando planes asignados...");
            DB::table('plan_transactions')->whereIn('patient_id', $patientIds)->delete();
            DB::table('assigned_plans')->whereIn('patient_id', $patientIds)->delete();

            // 2. Eliminar citas programadas
            $this->info("- Eliminando citas programadas...");
            DB::table('appointments')->whereIn('patient_id', $patientIds)->delete();

            // 3. Eliminar compras y servicios adquiridos
            $this->info("- Eliminando compras y servicios...");
            DB::table('acquired_services')->whereIn('patient_id', $patientIds)->delete();
            DB::table('patient_items')->whereIn('patient_id', $patientIds)->delete();

            // 4. Eliminar historial de ajustes
            $this->info("- Eliminando historial de ajustes...");
            DB::table('medical_ajuste_modules')->whereIn('patient_id', $patientIds)->delete();

            // 5. Eliminar historial de terapia física
            $this->info("- Eliminando historial de terapia física...");
            DB::table('medical_terapia_tracion_modules')->whereIn('patient_id', $patientIds)->delete();

            // 6. Eliminar antecedentes médicos
            $this->info("- Eliminando antecedentes médicos...");
            DB::table('medical_records')->whereIn('patient_id', $patientIds)->delete();
            DB::table('neurological_and_functional_evaluations')->whereIn('patient_id', $patientIds)->delete();
            DB::table('patient_medical_data')->whereIn('patient_id', $patientIds)->delete();
            DB::table('physical_examinations')->whereIn('patient_id', $patientIds)->delete();
            DB::table('spine_evaluations')->whereIn('patient_id', $patientIds)->delete();
            DB::table('diagnosis_and_treatments')->whereIn('patient_id', $patientIds)->delete();
            DB::table('patient_progress_after_treatments')->whereIn('patient_id', $patientIds)->delete();
            DB::table('history_medicals')->whereIn('patient_id', $patientIds)->delete();

            // 7. Eliminar otros datos relacionados
            $this->info("- Eliminando otros datos relacionados...");
            DB::table('waiting_rooms')->whereIn('patient_id', $patientIds)->delete();
            DB::table('patient_radiology_images')->whereIn('patient_id', $patientIds)->delete();
            DB::table('physical_therapy_categories_patient')->whereIn('patient_id', $patientIds)->delete();
            DB::table('medical_consultation_modules')->whereIn('patient_id', $patientIds)->delete();
            DB::table('medical_comparacion_reporte_modules')->whereIn('patient_id', $patientIds)->delete();
            DB::table('calls')->whereIn('patient_id', $patientIds)->delete();
            DB::table('insurances')->whereIn('patient_id', $patientIds)->delete();
            DB::table('coupons')->whereIn('patient_id', $patientIds)->delete();
            DB::table('credit_notes')->whereIn('patient_id', $patientIds)->delete();
            DB::table('coupon_notifications')->whereIn('patient_id', $patientIds)->delete();
            DB::table('pre_authorizations')->whereIn('patient_id', $patientIds)->delete();
            DB::table('patient_documents')->whereIn('patient_id', $patientIds)->delete();
            DB::table('invoices')->whereIn('patient_id', $patientIds)->delete();
            DB::table('related_in_centers')->whereIn('patient_id', $patientIds)->delete();
            DB::table('programming_history_notes')->whereIn('patient_id', $patientIds)->delete();
            DB::table('programming_histories')->whereIn('patient_id', $patientIds)->delete();
            DB::table('binnacles')->whereIn('patient_id', $patientIds)->delete();
            DB::table('emergency_contacts')->whereIn('patient_id', $patientIds)->delete();

            // 8. Eliminar balance/saldo
            $this->info("- Eliminando balance...");
            // Asumiendo que existe una tabla de balance o saldo
            if (DB::getSchemaBuilder()->hasTable('patient_balances')) {
                DB::table('patient_balances')->whereIn('patient_id', $patientIds)->delete();
            }

            DB::commit();

            $this->info("✓ Datos eliminados exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al eliminar datos: " . $e->getMessage());
            return;
        }

        // Ahora ejecutar los comandos de migración
        $this->info("\n=== Iniciando re-migración de datos ===\n");

        $this->info("Re-migrando planes asignados...");
        $this->call('migrate:planes-asignados');

        $this->info("Re-migrando citas programadas...");
        $this->call('migrate:citas-programadas');

        $this->info("Re-migrando compras...");
        $this->call('migrate:compras');

        $this->info("Re-migrando historial de ajustes...");
        $this->call('migrate:historial-ajuste');

        $this->info("Re-migrando historial de terapia física...");
        $this->call('migrate:historial-terapia-fisica');

        $this->info("Re-migrando antecedentes...");
        $this->call('migrate:antecedentes');

        $this->info("Re-migrando balance...");
        $this->call('migrate:balance');

        $this->info("\n✓ Re-migración completada exitosamente para las branches: " . implode(', ', $branches));
    }
}







