<?php

namespace App\Models;


class EmergencyContact extends BaseModel
{
    protected $fillable = [
        'full_name',
        'patient_id',
        'relationship_id',
        'mobile',
        'phone',
    ];

    public function relationship()
    {
        return $this->belongsTo(Relationship::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}