<?php

namespace App\Models;

class RelatedInCenter extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'patient_relationship_id',
        'relationship_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function patient_relationship()
    {
        return $this->belongsTo(Patient::class, 'patient_relationship_id');
    }

    public function relationship()
    {
        return $this->belongsTo(Relationship::class);
    }
}