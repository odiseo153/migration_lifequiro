<?php

namespace App\Models;


class ProgrammingHistoryNotes extends BaseModel
{
    protected $fillable = [
        'note',
        'action',
        'patient_id'
    ];


    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function getActionAttribute($value)
    {
        return strtolower($value);
    }
}



