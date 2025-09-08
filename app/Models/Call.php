<?php

namespace App\Models;


class Call extends BaseModel
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'comment',
    ];
    

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
