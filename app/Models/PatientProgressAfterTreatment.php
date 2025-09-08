<?php

namespace App\Models;


class PatientProgressAfterTreatment extends BaseModel
{
protected $fillable = [
        'patient_id',
        'note'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
