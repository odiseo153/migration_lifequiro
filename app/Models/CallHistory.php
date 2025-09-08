<?php

namespace App\Models;

use App\Enums\AppointmentStatus;


class CallHistory extends BaseModel
{
    protected $fillable = ['note','appointment_id','user_id','old_status','new_status'];

    protected $cast = [
        'old_status'=>AppointmentStatus::class, 
        'new_status'=>AppointmentStatus::class
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
