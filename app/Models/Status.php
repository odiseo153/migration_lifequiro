<?php

namespace App\Models;


class Status extends BaseModel
{
    protected $fillable = [
        'state',
        'order',
    ];

    public function appointments(){
        return $this->hasMany(Appointment::class);
    }
} 
