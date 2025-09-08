<?php

namespace App\Models;


class AppointmentBinacle extends BaseModel
{
    protected $fillable = [
        'comment',
        'appointment_id',
    ];

    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
}
