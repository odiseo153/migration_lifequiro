<?php

namespace App\Models;


class PatientItemAssignedPlan extends BaseModel
{
    protected $fillable = ['patient_id', 'item_id', 'assigned_plan_id', 'total'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }


    public function assignedPlan()
    {
        return $this->belongsTo(AssignedPlan::class);
    }

}
