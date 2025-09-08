<?php

namespace App\Models;


class PlanConsume extends BaseModel
{
    protected $fillable = [
        'assigned_plan_id',
        'consumed',
        'balance',
    ];

    public function assigned_plan()
    {
        return $this->belongsTo(AssignedPlan::class);
    }
}

