<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\AssignedPlan;
use App\Models\PaymentMethod;
use App\Models\Legacy\Factura;
use App\Models\PlanTransaction;
use App\Models\TransactionType;

class MigrateFactura extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:facturas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos desde facturas (legacy) hacia facturas (nuevo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración de facturas...");

        Factura::chunk(500, function ($facturas) {
            foreach ($facturas as $f) {
                if (!Patient::find($f->paciente_id)) {
                    $this->warn("Paciente no encontrado - ID: {$f->paciente_id}. Omitiendo registro.");
                    continue;
                }

                Invoice::updateOrCreate([
                    'id' => $f->id,
                ], [
                    'id' => $f->id,
                    'patient_id'  => $f->paciente_id,
                    'branch_id'  => $f->centro_id ==0 ? 1 : $f->centro_id,
                    'payment_method_id'  => $this->DeterminePaymentType($f->tipo),
                    'transaction_type_id'  => $this->DetermineTransactionType($f),
                    'type_of_tax_receipt_id'  => 1,
                    'no_invoice'  => $f->factura_no ,
                    'invoice_token'  => $f->no_comprobante == '' || $f->no_comprobante ==0 ?'GENERATED-'. $this->generateRandomCode(Invoice::class,8,'invoice_token') : $f->no_comprobante,
                    'total'  => $f->monto =='' || $f->monto ==null ? 0 : (int) $f->monto,
                    'note'  => $f->observacion,
                    'pre_authorization_id'  => $f->pre_autorizacion_id,
                    'created_at'  =>$f->fecha == '0000-00-00 00:00:00' ? now() : $f->fecha,
                ]);

            if($f->ajuste_plan_id != 0 && AssignedPlan::find($f->ajuste_plan_id)){
                PlanTransaction::create([
                    'assigned_plan_id' => $f->ajuste_plan_id,
                    'patient_id' => $f->paciente_id,
                    'amount' => $f->monto =='' || $f->monto ==null ? 0 : (int) $f->monto,
                    'transaction_type' => 'factura de abono a plan, de migracion',
                    'description' => 'Factura',
                ]);
            }


            }
        });

        $this->info("Migración completada de facturas.");
    }

    public function DeterminePaymentType($tipo)
    {
        return match($tipo){
            PaymentMethod::where('id', $tipo)->exists() => $tipo,
            default => 1,
        };
    }

    public function DetermineTransactionType($f)
    {
        return match($f){
            $f->ajuste_plan_id != 0 && AssignedPlan::find($f->ajuste_plan_id) => \App\Enums\TransactionType::PAGO_PLAN->value,
            default => 1,
        };
    }

}

