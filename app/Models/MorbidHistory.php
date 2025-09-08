<?php

namespace App\Models;


class MorbidHistory extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'diabetes_mellitus',
        'blood_pressure',
        'stroke',
        'cancer',
        'others',
        'previous_surgeries',
        'rheumatoid_arthritis',
        'osteoporosis',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}


