<?php

namespace App\Models;

class CancelledAppointment extends BaseModel
{

    protected $fillable = ['appointment_id', 'user_id', 'comment'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
