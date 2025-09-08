<?php

namespace App\Models;

use App\Enums\ArsServiceStatus;


class PatientItem extends BaseModel
{
    protected $fillable = [
        'item_id',
        'ars_id',
        'patient_id',
        'invoice_id',
        'type',
        'quantity',
        'individual_cost',
        'discount',
        'coberture_percent',
        'quantity',
        'no_document',
        'total',

        //Ars fields
        'no_authorization',
        'status',
        'is_completed',
        'is_approved',
        'ars_state',
        'ars_paid_amount',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'status' => 'integer',
        'is_approved' => 'boolean',
        'ars_state' => ArsServiceStatus::class,
    ];

    public function waiting_room()
    {
        return $this->hasOne(WaitingRoom::class);
    }

    public function voucher()
    {
        return $this->belongsToMany(Voucher::class,'voucher_patient_item');
    }

   
    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function acquired_services()
    {
        return $this->hasOne(AcquiredService::class)->withTrashed();
    }

    public function item_ars()
    {
        return $this->hasOne(ItemArs::class,'item_id','item_id');
    }

    public function insurance()
    {
        return $this->hasOneThrough(
            Insurance::class,
            Patient::class,
            'id',
            'ars_id',
            'patient_id',
            'id'
        );
    }

    public function ars()
    {
        return $this->belongsTo(Ars::class,'ars_id');
    }

    public function services_invoice_ars()
    {
        return $this->hasOne(ServicesInvoiceArs::class,'service_id','id');
    }
    
    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function radiology_image()
    {
        return $this->hasOne(PatientRadiologyImage::class);
    }

    public function service_document()
    {
        return $this->hasOne(ServiceDocument::class, 'service_id');
    }

    public function module_ajuste()
    {
        return $this->hasOne(MedicalAjusteModule::class, 'service_id');
    }

    public function module_terapia_traccion()
    {
        return $this->hasOne(MedicalTerapiaTracionModule::class, 'service_id');
    }

    public function module_comparacion_reporte()
    {
        return $this->hasOne(MedicalComparacionReporteModule::class, 'service_id');
    }

    public function module_consulta()
    {
        return $this->hasOne(MedicalConsultationModule::class, 'patient_item_id');
    }

    public function module_radiologia()
    {
        return $this->hasMany(PatientRadiologyImage::class, 'patient_item_id');
    }

   

}

