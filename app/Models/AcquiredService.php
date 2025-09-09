<?php

namespace App\Models;

use App\Models\Appointment;

class AcquiredService extends BaseModel
{
    protected $fillable = [
        'price',
        'status',
        'cancel_at',
        'patient_item_id',
        'patient_id',
        'plan_item_id',
        'assigned_plan_id'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class,'patient_item_id');
    }

    public function assigned_plan()
    {
        return $this->belongsTo(AssignedPlan::class,'assigned_plan_id')->withTrashed();
    }

    public function waiting_room()
    {
        return $this->hasOne(WaitingRoom::class,'service_id');
    }

    public function patient_item()
    {
        return $this->belongsTo(PatientItem::class,'patient_item_id')->withTrashed();
    }

    public function patient_plan_item()
    {
        return $this->belongsTo(Item::class, 'plan_item_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->hasOneThrough(Invoice::class, PatientItem::class, 'id', 'id', 'patient_item_id');
    }

    public function medical_consultation_module()
    {
        return $this->hasOne(MedicalConsultationModule::class, 'patient_item_id', 'patient_item_id')
            ->orWhere(function($query) {
                $query->where('patient_item_id', $this->patient_item_id)
                      ->where('patient_id', $this->patient_id);
            });
    }

    public function medical_ajuste_module()
    {
        return $this->hasOne(MedicalAjusteModule::class, 'acquired_service_id', 'id')
            ->orWhere(function($query) {
                $query->where('service_id', $this->patient_item_id)
                      ->where('patient_id', $this->patient_id);
            })
            ->orWhere(function($query) {
                $query->where('plan_item_id', $this->plan_item_id)
                      ->where('patient_id', $this->patient_id);
            });
    }

    public function medical_terapia_tracion_module()
    {
        return $this->hasOne(MedicalTerapiaTracionModule::class, 'acquired_service_id', 'id')
            ->orWhere(function($query) {
                $query->where('service_id', $this->patient_item_id)
                      ->where('patient_id', $this->patient_id);
            })
            ->orWhere(function($query) {
                $query->where('plan_item_id', $this->plan_item_id)
                      ->where('patient_id', $this->patient_id);
            });
    }

    /**
     * Relación con MedicalComparacionReporteModule
     * Busca el módulo que coincida con service_id
     */
    public function medical_comparacion_report_module()
    {
        return $this->hasOne(MedicalComparacionReporteModule::class, 'service_id', 'patient_item_id')
        ->orWhere(function($query) {
            $query->where('service_id', $this->patient_item_id)
                  ->where('patient_id', $this->patient_id);
        });
    }



    public function appointment()
    {
        // Si es un servicio de tarjeta (PatientItem)
        if ($this->patient_item_id) {
            $voucher = Voucher::whereHas('patient_items', function($query) {
                $query->where('patient_items.id', $this->patient_item_id);
            })->first();

            return $voucher ? Appointment::find($voucher->appointment_id) : null;
        }
        if ($this->plan_item_id) {
            $voucher = Voucher::join('voucher_plan_items', 'vouchers.id', '=', 'voucher_plan_items.voucher_id')
                ->join('items', 'items.id', '=', 'voucher_plan_items.item_id')
                ->where('items.id', $this->plan_item_id)
                ->whereNull('items.deleted_at')
                ->select('vouchers.*')
                ->orderBy('vouchers.id', 'desc')
                ->first();

            return $voucher ? $voucher->appointment : null;
        }

        return null;
    }

    public function voucher()
    {
        if ($this->patient_item_id) {
            return Voucher::whereHas('patient_items', function($query) {
                $query->where('patient_items.id', $this->patient_item_id);
            })->first();
        }

        if ($this->plan_item_id) {
            return Voucher::join('voucher_plan_items', 'vouchers.id', '=', 'voucher_plan_items.voucher_id')
                ->join('items', 'items.id', '=', 'voucher_plan_items.item_id')
                ->where('items.id', $this->plan_item_id)
                ->whereNull('items.deleted_at')
                ->select('vouchers.*')
                ->orderBy('vouchers.id', 'desc')
                ->first();
        }

        return null;
    }

/*
protected static function booted()
{
        static::updated(function ($acquired_service) {
            $acquired_service->refresh();
            event(new AcquiredServiceModificated($acquired_service, 'update'));
        });

        static::created(function ($acquired_service) {
            $acquired_service->refresh();
            event(new AcquiredServiceModificated($acquired_service, 'create'));
        });
    }
    */
}

