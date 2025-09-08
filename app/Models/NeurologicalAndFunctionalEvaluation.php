<?php

namespace App\Models;

class NeurologicalAndFunctionalEvaluation extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'reflexes',
        'reflexes_details',
        'sensitivity',
        'sensitivity_details',
        'gait',
        'gait_details',
        'daily_activities',
        'daily_activities_details',
        'technical_aids',
        'technical_aids_details',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function getReflexesAttribute($value)
    {
        return ucfirst($value);
    }

    public function getSensitivityAttribute($value)
    {
        return ucfirst($value);
    }

    public function getGaitAttribute($value)
    {
        return ucfirst($value);
    }

    public function getDailyActivitiesAttribute($value)
    {
        return ucfirst($value);
    }

    public function getTechnicalAidsAttribute($value)
    {
        return ucfirst($value);
    }
}
