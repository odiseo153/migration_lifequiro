<?php

namespace App\Models;

class MedicalRecord extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'id',
        'has_chronic_disease',
        'chronic_disease_details',
        'has_allergies',
        'allergy_type',
        'hospitalized_last_year',
        'hospitalization_reason',
        'has_disability',
        'disability_type',
        'consultation_reason',
        'symptoms_impact_on_life',
        'medical_history',
        'current_medication',
        'pain_areas'
    ];

    protected $casts = [
        'has_chronic_disease' => 'boolean',
        'has_allergies' => 'boolean',
        'hospitalized_last_year' => 'boolean',
        'has_disability' => 'boolean',
        'pain_areas' => 'json'
    ];

    protected $appends = ['areas'];
    protected $hidden = ['pain_areas', 'patient']; // Ocultar patient para evitar recursión

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function getAreasAttribute()
    {
        // $pain_areas ya es un arreglo/objeto por el cast 'json'
        return $this->pain_areas ?? [];
    }
}
