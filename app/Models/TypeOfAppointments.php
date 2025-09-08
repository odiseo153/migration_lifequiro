<?php

namespace App\Models;


class TypeOfAppointments extends BaseModel
{
    protected $fillable = ['name'];

   
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
