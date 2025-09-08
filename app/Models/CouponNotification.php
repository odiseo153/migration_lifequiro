<?php

namespace App\Models;


class CouponNotification extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'is_used',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
