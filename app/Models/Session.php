<?php

namespace App\Models;


class Session extends BaseModel
{
    protected $fillable = [
        'assigned_plan_id',
        'total',
        'remaing',
        'used',
        'plan_sessions'

    ];

    public function assigned_plan()
    {
        return $this->belongsTo(AssignedPlan::class);
    }
}



