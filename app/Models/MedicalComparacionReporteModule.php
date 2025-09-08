<?php

namespace App\Models;

use App\Enums\ServicesStatus;
use App\Enums\AppointmentStatus;


class MedicalComparacionReporteModule extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'service_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function service()
    {
        return $this->belongsTo(PatientItem::class,'service_id');
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
