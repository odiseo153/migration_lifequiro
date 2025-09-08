<?php

namespace App\Models;


class PainEvaluation extends BaseModel
{
    protected $fillable = ['pain_description','patient_id'];


    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
