<?php

namespace App\Models;


class PatientGroup extends BaseModel
{
    protected $fillable = [
        'name',
    ];

    public function patients(){
        return $this->hasMany(Patient::class);
    }
}
