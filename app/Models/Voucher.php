<?php

namespace App\Models;


class Voucher extends BaseModel
{
    protected $fillable = [
        'appointment_id',
        'assigned_plan_id',
        'status',
        'quantity',
        'price'
    ];

    protected $appends = ['patient'];
    public function patient_items()
    {
        return $this->belongsToMany(PatientItem::class,'voucher_patient_item');
    }

    public function services()
    {
        return $this->belongsToMany(PatientItem::class,'voucher_patient_item');
    }
    
    public function plan_items()
    {
      return $this->belongsToMany(Item::class,'voucher_plan_items');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'voucher_patient_item', 'voucher_id', 'patient_item_id')
                    ->join('patient_items', 'voucher_patient_item.patient_item_id', '=', 'patient_items.id')
                    ->whereColumn('patient_items.item_id', 'items.id')
                    ->select('items.*');
    }

    public function assigned_plan()
    {
        return $this->belongsTo(AssignedPlan::class);
    }
    
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'voucher_patient_item', 'voucher_id', 'patient_item_id')
        ->join('patient_items', 'voucher_patient_item.patient_item_id', '=', 'patient_items.id')
        ->join('invoices', 'patient_items.invoice_id', '=', 'invoices.id')
        ->where('voucher_patient_item.voucher_id', $this->id);
    }


    public function getPatientAttribute()
    {
        $appointment = $this->appointment;
        if($appointment){
            return [
                'id' => $appointment->patient->id,
                'first_name' => $appointment->patient->first_name,
                'last_name' => $appointment->patient->last_name,
                'email' => $appointment->patient->email,
                'mobile' => $appointment->patient->mobile
            ];
        }
        return null;
    }
}
