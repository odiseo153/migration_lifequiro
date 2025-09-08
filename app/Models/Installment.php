<?php

namespace App\Models;


class Installment extends BaseModel
{
    protected $fillable = [
        'date_paid',
        'amount',
        'is_it_paid',
        'assigned_plan_id'
    ];

    public function assigned_plan()
    {
        return $this->belongsTo(AssignedPlan::class);
    }

}
