<?php

namespace App\Models;


class PhysicalExamination extends BaseModel
{

    protected $fillable = [
        'patient_id',
        'weight_status',
        'weight_specify',
        'height_status',
        'height_specify',
        'size_status',
        'size_specify',
        'physical_activity',
        'specify_physical_activity',
        'injuries_description',
        'posture_status',
        'posture_specify',
        'body_symmetry',
        'asymmetry_details',
        'has_pain',
        'pain_scale',
        'pain_location',
        'pain_intensity',
        'pain_factors',
        'has_cramps',
        'cramps_details',
        'has_tingling',
        'tingling_details',
        'hip',
        'knee',
        'ankle',
        'shoulder',
        'elbow',
        'wrist',
        'muscle_strength',
        'upper_limbs_strength',
        'lower_limbs_strength',
        'trunk_strength',
        'muscle_strength_observations',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function getDailyActivitiesAttribute($value)
    {
        return ucfirst($value);
    }
}