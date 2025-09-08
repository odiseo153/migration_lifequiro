<?php

namespace App\Models;

use App\Enums\ServicesStatus;
use App\Enums\AppointmentStatus;


class MedicalConsultationModule extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'patient_item_id',
        'plan_item_id',
        'pain_zones',
        'pain_intensity',
        'cervical_vertebrae',
        'thoracic_vertebrae',
        'lumbar_vertebrae',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function patient_item()
    {
        return $this->belongsTo(PatientItem::class, 'patient_item_id');
    }

    public function plan_item()
    {
        return $this->belongsTo(Item::class, 'plan_item_id');
    }
    
    public function checkAppointmentStatusOnService(AcquiredService $service): void
    {
        $patient = $service->patient;
        $sameAppointment = $patient->acquired_services()
            ->where('patient_item_id', '!=', $service->id)
            ->get()
            ->filter(
                fn($s) => $s->appointment() !== null && $s->appointment()->id === $service->appointment()->id
            );

        if (
            $sameAppointment->isEmpty() || 
            $sameAppointment->every(fn($s) => $s->status == ServicesStatus::COMPLETADA)
            )
            {
            $service->appointment()->update(['status_id' => AppointmentStatus::COMPLETADA->value]);
        }
    }
}
