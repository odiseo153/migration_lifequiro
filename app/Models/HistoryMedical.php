<?php

namespace App\Models;

class HistoryMedical extends BaseModel
{

    protected $fillable = [
        'patient_id',
        'consultation_reason',
        'personal_background',
        'family_background',
        'symptoms',
        'other_symptoms',
        'previous_diagnoses',
        'current_medication',
        'diagnostic_tests',
        'other_test',
        'analytics',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    
}
