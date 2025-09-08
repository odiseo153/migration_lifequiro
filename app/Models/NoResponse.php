<?php

namespace App\Models;


class NoResponse extends BaseModel
{
    protected $fillable = [
        'appointment_id',
        'user_id'
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
