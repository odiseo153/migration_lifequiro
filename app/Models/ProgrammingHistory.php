<?php

namespace App\Models;


class ProgrammingHistory extends BaseModel
{
    protected $fillable = [
        'day',
        'hour',
        'branch_id',
        'patient_id',
        'assigned_plan_id',
        'activation_date',
        'is_active',
        'pre_authorization_id'
    ];


    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assigned_plan()
    {
        return $this->belongsTo(AssignedPlan::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function pre_authorization()
    {
        return $this->belongsTo(PreAuthorization::class);
    }

}


