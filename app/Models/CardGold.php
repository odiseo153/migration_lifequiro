<?php

namespace App\Models;


class CardGold extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'expiration_date',
        'is_used',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
