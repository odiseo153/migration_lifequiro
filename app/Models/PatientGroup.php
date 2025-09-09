<?php

namespace App\Models;


class PatientGroup extends BaseModel
{
    protected $fillable = [
        'id',
        'name',
        'created_at',
    ];

    public function patients(){
        return $this->hasMany(Patient::class);
    }
}
