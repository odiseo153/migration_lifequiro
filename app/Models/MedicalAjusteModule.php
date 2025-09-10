<?php

namespace App\Models;

use App\Enums\ServicesStatus;
use App\Enums\AppointmentStatus;


class MedicalAjusteModule extends BaseModel
{

    protected $fillable = [
        'id',
        'patient_id',
        'service_id',
        'acquired_service_id',
        'plan_item_id',
        'pain_intensity',
        'cervical_vertebrae',
        'thoracic_vertebrae',
        'lumbar_vertebrae',
        'note',
        'created_at',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function patient_item()
    {
        return $this->belongsTo(PatientItem::class, 'service_id');
    }

    public function acquired_service()
    {
        return $this->belongsTo(AcquiredService::class, 'acquired_service_id');
    }

    public function plan_item()
    {
        return $this->belongsTo(Item::class,'plan_item_id');
    }


    public function checkAppointmentStatusOnService(AcquiredService $service,$serviceColumn,$serviceId): void
    {
        $patient = $service->patient;
        $sameAppointment = $patient->acquired_services()
            ->where($serviceColumn, '!=', $serviceId)
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
