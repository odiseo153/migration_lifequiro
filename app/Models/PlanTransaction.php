<?php

namespace App\Models;

class PlanTransaction extends BaseModel
{
    protected $fillable = [
        'assigned_plan_id',
        'patient_id',
        'amount',
        'transaction_type',
        'description',
    ];


    public function assigned_plan()
    {
        return $this->belongsTo(AssignedPlan::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
