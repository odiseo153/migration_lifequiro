<?php

namespace App\Models;


class PatientMedicalData extends BaseModel
{

    protected $fillable = [
        'patient_id',
        'x_rays',
        're_evaluation',
        'frecuency',
        'answer',
        'duration',
        'start_plan',
        'observations',
        'observations_x_rays'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /*
     public function getStartPlanAttribute($value)
     {
        return $this->patient->assigned_plan ? $this->patient->assigned_plan->plan->type_of_plan : null;
    }
    */

}