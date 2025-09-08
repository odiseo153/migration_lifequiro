<?php

namespace App\Models;


class DiagnosisAndTreatment extends BaseModel
{

    protected $fillable = [
        'patient_id',
        'problem_diagnosis',
        'long_term_treatment',
        'short_term_treatment',
        'session_frequency',
        'treatment_modalities',
        'treatment_modalities_other',
        'reevaluation_date',
        'reevaluation_hour',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}